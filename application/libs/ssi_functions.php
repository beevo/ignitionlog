<?php

//	Program Name:		ssi_functions.php
//	Program Purpose:		Functions used Site Wide	
//	Created by:			CHAD
//	Program Modifications:


// Includes for calls to i5 Programs
// include_once 'ToolkitService.php';
// include_once 'helpshow.php';

// default values: if UserIDs and Passwords are left blank, profile NOBODY will be used.
$pf_i5UserID = '';
$pf_i5Password = '';
$pf_i5IPAddress = '';

/* DB2 connection details */
$pf_db2SystemName = '';
$pf_db2UserID = '';
$pf_db2Password = '';

/* MySQL connection details */
$pf_mysqlUrl      = '';
$pf_mysqlUserId   = '';
$pf_mysqlPassword = '';
$pf_mysqlDataBase = '';

$pf_orclDB = '';
$pf_orclUserId = '';
$pf_orclPassword = '';

/* Encryption key for si_encrypt, si_decrypt functions */
$pf_encrypt_key;

function si_set_libl($liblname)
{
	$liblname = strtoupper($liblname);
	global $pf_liblLibs;

	$parametersIn = array (
		array ("name"=>"LIBLNM", "io"=>I5_IN, "type" => I5_TYPE_CHAR,"length"=> 10),
		array ("name"=>"RTVLIBL","io"=>I5_IN, "type" => I5_TYPE_CHAR,"length"=> 550),
		array ("name"=>"SETRTN", "io"=>I5_INOUT, "type" => I5_TYPE_CHAR,"length"=> 1));

	$pgm = i5_program_prepare("si_webspt/si_setlibl",$parametersIn);
	if (!$pgm)
	{
		$errorTab = i5_error();
		var_dump($errorTab);
		return false;
	}

	// if we need to use *FILES, then construct the list of files
	$AllLibs = '';
	if ($liblname == '*FILES')
	{
		foreach ($pf_liblLibs as $lib)
		{
			$AllLibs .= $lib . ' ';
		}
	}

	$pgmcall = i5_program_call($pgm,
				array("LIBLNM" =>$liblname,"RTVLIBL" =>$AllLibs),
				array("SETRTN" => "rtnVal"));
	if (!$pgmcall)
	{
		$errorTab = i5_error();
		var_dump($errorTab);
		return false;
	}

	// get return values
	if($rtnVal != "0")
	{
		return false;
	}

	// if we haven't failed yet, then we succeeded!
	return true;
}

function si_i5_connect()
{
	// DB Connection code
	$conn = i5_connect($GLOBALS['pf_i5IPAddress'],
			$GLOBALS['pf_i5UserID'],
			$GLOBALS['pf_i5Password']);
	return $conn;
}

function si_db2_connect($options)
{
	$conn = db2_connect($GLOBALS['pf_db2SystemName'],
				        $GLOBALS['pf_db2UserID'],
				        $GLOBALS['pf_db2Password'],
				        $options);
	return $conn;
}

function si_mysql_connect()
{
	$conn = mysql_connect($GLOBALS['pf_mysqlUrl'],
						  $GLOBALS['pf_mysqlUserId'],
						  $GLOBALS['pf_mysqlPassword']);
	  	return $conn;
}

function si_mysql_select_db($conn)
{

	$db_selected = mysql_select_db($GLOBALS['pf_mysqlDataBase'], $conn);

	return $db_selected;
}

function si_oci_connect()
{
	// DB Connection code
	$conn = oci_connect(
			$GLOBALS['pf_orclUserId'],
			$GLOBALS['pf_orclPassword'],
			$GLOBALS['pf_orclDB']);
	return $conn;
}

function si_oci_errormsg()
{
	$e = oci_error();
	return $e['message'];
}

function si_oci_query($conn, $sqlstmt)
{
	$stmt = oci_parse($conn, $sqlstmt);
	if (!$stmt)
	{
		die("<b>Error ".si_oci_errormsg()."</b>");
	}
	if (!oci_execute($stmt)) 
	{
		die("<b>Error ".si_oci_errormsg()."</b>"); 
	}
	return $stmt;
}

function si_raw_to_array($rawstring, $needquote = true)
{
    $arr = array();

    $decodedstring = urldecode($rawstring);

    // get an array of all the key/value pairs
	 $variables = split('[&;]',$decodedstring);
	 foreach($variables as $var)
	 {
	 	// split them by the equal sign and stick
	 	// them in an associative array indexed by
	 	// variable name
	 	list($key, $value) = explode("=", $var);
	 	if($needquote)
			$arr[$key][] = si_quote_string($value);
		else
			$arr[$key][] = $value;
     }

    return $arr;
}

function si_quote_string($string)
{
	return str_replace("'", "''", $string);
}

function si_set_row_color($color1, $color2)
{
	global $pf_altrowclr;
	// set the color
	if(!isset($pf_altrowclr) || $pf_altrowclr == $color2)
		$pf_altrowclr = $color1;
	else
		$pf_altrowclr = $color2;
}

function si_encrypt($plain_text, $encryption_key = null)
{
	/* globalize encryption key from si_user_preferences.php */
	global $pf_encrypt_key;
	if(!isset($encryption_key))
	{
	      /* If the encryption key from si_user_preferences.php
	         is not set return the plain text */
	      if(isset($pf_encrypt_key))
	      	$encryption_key = $pf_encrypt_key;
	      else
	      	return $plain_text;
	}
	/* Open module, and create the initialization vector */
	$encryption_descriptor = mcrypt_module_open(MCRYPT_TWOFISH, '', MCRYPT_MODE_ECB, '');
	$encryption_key = substr($encryption_key, 0, mcrypt_enc_get_key_size($encryption_descriptor));
	$init_vector = mcrypt_create_iv(mcrypt_enc_get_iv_size($encryption_descriptor), MCRYPT_RAND);

	/* Initialize encryption handle */
	if (mcrypt_generic_init($encryption_descriptor, $encryption_key, $init_vector) != -1)
	{
		/* Encrypt data */
		$cipher_text = mcrypt_generic($encryption_descriptor, $plain_text);

		/* Clean up */
		mcrypt_generic_deinit($encryption_descriptor);
	}

	mcrypt_module_close($encryption_descriptor);

	return $cipher_text;
	// ...end Encrypt Data
}

function si_decrypt($cipher_text, $encryption_key = null)
{
	/* globalize encryption key from si_user_preferences.php */
	global $pf_encrypt_key;
	if(!isset($encryption_key))
	 {
	      /* If the encryption key from si_user_preferences.php
	         is not set return the cipher text */
	      if(isset($pf_encrypt_key))
	      	$encryption_key = $pf_encrypt_key;
	      else
	      	return $cipher_text;
	}
	/* Open module, and create the initialization vector */
	$encryption_descriptor = mcrypt_module_open(MCRYPT_TWOFISH, '', MCRYPT_MODE_ECB, '');
	$encryption_key = substr($encryption_key, 0, mcrypt_enc_get_key_size($encryption_descriptor));
	$init_vector = mcrypt_create_iv(mcrypt_enc_get_iv_size($encryption_descriptor), MCRYPT_RAND);

	/* Initialize decryption handle */
	if (mcrypt_generic_init($encryption_descriptor, $encryption_key, $init_vector) != -1)
	{
		/* decrypt data */
		$plain_text = mdecrypt_generic ($encryption_descriptor, $cipher_text);

		/* Clean up */
		mcrypt_generic_deinit($encryption_descriptor);
	}

	mcrypt_module_close($encryption_descriptor);

	return $plain_text;
	// ...end Decrypt Data
}

/*****************************
 Check for a Cart ID
 *****************************/
function check_cart($usr, $cart, $db2conn)
{
	$query = "select WHCART FROM WCPWEB/WEBCARTH WHERE WHUSRID = ? and WHCART = ? and WHSTAT = 'O'";
	$stmt = db2_prepare($db2conn, $query);
	if (!($stmt))
	{
	    db2_close($db2conn);
	    die("<b>Error1 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
	}
	
    db2_bind_param($stmt, 1, "usr", DB2_PARAM_IN);
	db2_bind_param($stmt, 2, "cart", DB2_PARAM_IN);
	
    db2_execute($stmt);
    if ($row = db2_fetch_assoc($stmt))
    {
    	$tdystmp = date('Y-m-d-H.i.s');
        $queryU = "update WCPWEB/WEBCARTH set (WHCGDTE) = (?) where WHCART = ? with NC";
        $stmtU = db2_prepare($db2conn, $queryU);
        if (!($stmtU))
        {
            db2_close($db2conn);
            die("<b>ErrorU ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
        }        
    	$cart = $row['WHCART'];
        db2_bind_param($stmtU, 1, "tdystmp", DB2_PARAM_IN);
        db2_bind_param($stmtU, 2, "cart", DB2_PARAM_IN);
        if (!($setCheck = db2_execute($stmtU)))
        {
            // close the database connection
            db2_close($db2conn);
            die("<b>Error4 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
        }
    	return $row['WHCART'];
    } 			
    else
	{ 
	    $cart_num = create_cart($usr, $db2conn);
	$outHTML = '<a href="cart"><span>View Cart  ($0.00 - 0 items)</span></a>';

//	if( !($getCart_result = db2_fetch_both($getCart)) )
	?>
<script style="text/javascript" language="javascript">
	document.getElementById("cart").innerHTML = '<?php echo $outHTML; ?>';
</script>
<?
		return $cart_num;
	} 
}

/*****************************
 Create Cart ID
 *****************************/
function create_cart($usr, $db2conn)
{
	$tdystmp = date('Y-m-d-H.i.s');
	$query = "insert into WCPWEB/WEBCARTH (WHUSRID, WHSTAT, WHCRDTE, WHCGDTE) values ('". $usr . "', 'O', '" . $tdystmp ."', '". $tdystmp."')"; 
//	$query2 = "select WHBID from WCPWEB/WEBBSKH WHERE WHUSID = '". $usr . "' and WHSTAT = 'O'";

	// Fetch basket record if it exists 
	if (!($crtCart = db2_exec($db2conn, $query))) 
	{
		// close the database connection
		db2_close($db2conn);   
		
		die("<b>Error3 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>"); 
	}
	
	// Fetch basket record if it exists 
	return  db2_last_insert_id($db2conn);
//	return $getCart_result['WHBID'];
}

/***********************************
 Recalc Cart Totals
 ***********************************/
function recalc_cart($cart, $db2conn)
{
    $query = "select WDITEM, WDQTY, IMPUMC from WCPWEB/WEBCARTH left join WCPWEB/WEBCARTD on WHCART = WDCART left join ".FILELIB."/ITMST on WDITEM = IMITNO where WHCART = ? order by WDISEQ";

	$stmt = db2_prepare($db2conn, $query);
	if (!($stmt))
	{
	    db2_close($db2conn);
	    die("<b>Error1 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
	}
	
	db2_bind_param($stmt, 1, "cart", DB2_PARAM_IN);
	db2_execute($stmt);

    $num_items = 0;
    $srtsq = 0;
    $itms = array();
    $a_itm = array();
    $AQOH = array();
    $Aprice = array();
    $Aprc1 = array();
    $Aprc2 = array();
    $Acde1 = array();
    $Acde2 = array();
    //is this suppose to be an assign or conditional ==?
    while($getCart_result = db2_fetch_both($stmt))
    {
        if (isset($getCart_result['WDITEM']) && !empty($getCart_result['WDITEM']))
        {
            $itms[$getCart_result['WDITEM']] = $getCart_result['WDITEM'];
            $a_itm[$getCart_result['WDITEM']][0] = $getCart_result['WDITEM'];
            $a_itm[$getCart_result['WDITEM']][1] = $getCart_result['WDQTY'];
            $a_itm[$getCart_result['WDITEM']][2] = $getCart_result['IMPUMC'];
            $AQOH[$getCart_result['WDITEM']] = $getCart_result['WDQTY'];
            $num_items = $num_items + 1;
        }
        else
        {
            $tot_item = 0;
            $tot_dols = 0;
        }
    }
    $cust = $_SESSION['cust_num'];
    $whid = $_SESSION['whid'];
    Get_CPrice($itms, $AQOH, $cust, $whid, $Aprice, $db2conn);
    //    Get_CPrice($itms, $AQOH, $cust, $whid, $Aprice, $Aprc1, $Aprc2, $Acde1, $Acde2, $Amqty1, $Amqty2, $num_items);
    $i = 0;
    $queryDU = "UPDATE WCPWEB/WEBCARTD SET(WDISEQ) = (?) where WDCART = ? and WDITEM = ? with NC";
	$stmtDU = db2_prepare($db2conn, $queryDU);
	if (!($stmt))
	{
	    db2_close($db2conn);
	    die("<b>Error1 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
	}
	
    foreach ($a_itm as $k => $v)
    {
        $item = trim($a_itm[$k][0]);
        $qty = $a_itm[$k][1];
        $tot_item = $qty * $Aprice[$item] * $a_itm[$k][2];
        $tot_dols = $tot_dols + $tot_item;
        $srtsq = $srtsq + 10;
	    db2_bind_param($stmtDU, 1, "srtsq", DB2_PARAM_IN);
	    db2_bind_param($stmtDU, 2, "cart", DB2_PARAM_IN);
	    db2_bind_param($stmtDU, 3, "item", DB2_PARAM_IN);
	    
        if ( !(db2_execute($stmtDU)) )
        {
            // close the database connection
            db2_close($db2conn);            	
            die("<b>Error ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
        }
        $i += 1;
    }

//    $queryU = "UPDATE HDWWEB/WEBBSKH SET(WHNITM, WHTOT$, WHPRMO) = (" . $num_items .", ". $tot_dols.", '". $promo. "') where WHBID = " . $cart . " with NC";

    // Fetch rows for page: relative to initial cursor
//    if (!($result = db2_exec($db2conn, $queryU)))
//    {
        // close the database connection
//        db2_close($db2conn);

//        die("<b>Error ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
//    }

    $outHTML = '<a href="'.path.'/cart"><span class="visible-desktop"><i class="icon-shopping-cart"></i>View Cart  ($'. number_format($tot_dols, 2, '.', ''). ' - '. $num_items . ' items)</span><span class="hidden-desktop"><i class="icon-shopping-cart"></i>'. $num_items.'</span></a>';
    ?>

<script style="text/javascript" language="javascript">
	document.getElementById("cart").innerHTML = '<?php echo $outHTML; ?>';
</script>
<?
}

/***********************************
 Quick Recalc Cart Totals
 ***********************************/
function quick_cart($cart, $db2conn)
{
    $query = "select WDITEM, WDQTY, IMPUMC from WCPWEB/WEBCARTH left join WCPWEB/WEBCARTD on WHCART = WDCART left join ".FILELIB."/ITMST on WDITEM = IMITNO where WHCART = ? order by WDISEQ";

    $stmt = db2_prepare($db2conn, $query);
	if (!($stmt))
	{
	    db2_close($db2conn);
	    die("<b>Error1 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
	}
	
	db2_bind_param($stmt, 1, "cart", DB2_PARAM_IN);
	db2_execute($stmt);

    $num_items = 0;
    $srtsq = 0;
    $itms = array();
    $a_itm = array();
    $AQOH = array();
    while($getCart_result = db2_fetch_both($stmt))
    {
        if (isset($getCart_result['WDITEM']) && !empty($getCart_result['WDITEM']))
        {
            $itms[$getCart_result['WDITEM']] = $getCart_result['WDITEM'];
            $a_itm[$getCart_result['WDITEM']][0] = $getCart_result['WDITEM'];
            $a_itm[$getCart_result['WDITEM']][1] = $getCart_result['WDQTY'];
            $AQOH[$getCart_result['WDITEM']] = $getCart_result['WDQTY'];
            $a_itm[$getCart_result['WDITEM']][2] = $getCart_result['IMPUMC'];
            $num_items = $num_items + 1;
        }
        else
        {
            $tot_item = 0;
            $tot_dols = 0;
        }
    }
    $cust = $_SESSION['cust_num'];
    $whid = $_SESSION['whid'];
    Get_CPrice($itms, $AQOH, $cust, $whid, $Aprice, $db2conn);
    
    foreach ($a_itm as $k => $v)
    {
        $item = trim($a_itm[$k][0]);
        $qty = $a_itm[$k][1];
        $tot_item = $qty * $Aprice[$item] * $a_itm[$k][2];
        $tot_dols = $tot_dols + $tot_item;
        $i += 1;
    }

    echo '<a href="'.path.'/cart"><span class="visible-desktop"><i class="glyphicon glyphicon-shopping-cart"></i> View Cart  ($'. number_format($tot_dols, 2, '.', ''). ' - '. $num_items . ' items)</span><span class="hidden-desktop"><i class="icon-shopping-cart"></i>'. $num_items.'</span></a>';
}

/*****************************
 Get Cart Item Price
 *****************************/
function Get_CPrice($itms, $AQOH, $cust, $whid, &$Aprice, $db2conn) 
{
    $query = "select IMLPR1, IMPUMC from ".FILELIB."/ITMST where IMITNO = ?";
    
    $stmt = db2_prepare($db2conn, $query);
    if (!($stmt))
    {
        db2_close($db2conn);
        die("<b>Error1 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
    }    
    foreach ($itms as $k => $v)
    {
        $item = trim($v);
        db2_bind_param($stmt, 1, "item", DB2_PARAM_IN);
        db2_execute($stmt);
        $getPrc = db2_fetch_both($stmt);
        $Aprice[$item] = $getPrc['IMLPR1']; 
    }
    
}

/*****************************
 Get Price
 *****************************/
function Get_Price($itms, $cust, $whid) 
{
    $extension='ibm_db2'; 
    try { 
        $ToolkitServiceObj = ToolkitService::getInstance($db, $user, $pass, $extension);     
    }

    catch (Exception $e) { 			
    	echo  $e->getMessage(), "\n";
    	exit();
    }

    $ToolkitServiceObj->setToolkitServiceParams(array('InternalKey'=>"/tmp/$user"));
	
    $cmd = "addlible ".FILELIB; 
    $ToolkitServiceObj->CLCommand($cmd); 
    
    $param[] = $ToolkitServiceObj->AddParameterChar('in', 27,'Items Array', 'RqsItms', $itms, 'off', 50);
    $param[] = $ToolkitServiceObj->AddParameterZoned('both', 11, 3,'Qty Array', 'RqsQty', $qty, 50);
    $param[] = $ToolkitServiceObj->AddParameterZoned('in', 10, 0,'Customer', 'RqsCsno', $cust);
    $param[] = $ToolkitServiceObj->AddParameterChar('both', 2, 'Warehouse', 'RqsWhID', $whid);
    $param[] = $ToolkitServiceObj->AddParameterZoned('both', 15, 5,'Price Array', 'RqsPrice', $price, 50);

    $result = $ToolkitServiceObj->PgmCall("PRCITEMS", "WCPWEB", $param, null, null);

    printf($result);
    printf($result['io_param']);
    if($result){
    	if ( $result['io_param']['PGMRPY'] != '' )
    		return $result['io_param']['PGMRPY'];
	   else
	       return 'Payment is successful.';
    }
    else
	   return "Execution failed.";
	
    /* Do not use the disconnect() function for "state full" connection */
    $ToolkitServiceObj->disconnect();

}

/*****************************
 Create Generic Dropdown
 *****************************/
function createDropdown($arr, $frm, $sel) {
    echo '<select class="selbox" name="'.$frm.'" id="'.$frm.'">';
    foreach ($arr as $key => $value)
    {
        echo '<option value="'.$value .'"';
        if($value == $sel)
        {
            echo " selected";
        }
        echo '>'.$value.'</option>';
    }
    echo '</select>';
}

/***********************************
 Create Page Dropdown Box for Search
 ***********************************/
function createPagedownS($arr, $frm, $sel, $max, $sw, $sort, $fv, $fh) 
{
    $ofv = implode('!', $fv);
    $ofh = implode('!', $fh);
    echo '<ul class="dropdown-menu">';
	foreach ($arr as $key => $value) 
	{
		if($value == $sel)
		{
	 		echo '<li class="active"><a href="'.path.'/search/'.urlencode($sw).'/'.$value.'/'.urlencode($sort).'/'.urlencode($ofh).'/'.urlencode($ofv).'">'.$value .'</a></li>';
		}
		else
	 		echo '<li><a href="'.path.'/search/'.urlencode($sw).'/'.$value.'/'.urlencode($sort).'/'.urlencode($ofh).'/'.urlencode($ofv).'">'.$value .'</a></li>';
	}
	echo '</ul>';
}

/*************************************
 Create Page Dropdown Box for Products
 *************************************/
function createPagedownP($arr, $frm, $sel, $max, $sw, $sort) 
{
	echo '<ul class="dropdown-menu">';
	foreach ($arr as $key => $value) 
	{
		if($value == $sel)
		{
	 		echo '<li class="active"><a href="'.path.'/search/'.$sw.'/'.$value.'/'.$sort.'">'.$value .'</a></li>';
		}
		else
	 		echo '<li><a href="'.path.'/search/'.$sw.'/'.$value.'/'.$sort.'">'.$value .'</a></li>';
	}
	echo '</ul>';
}

/***********************************
 Create breadcrumbs
 ***********************************/
function Crumbs() 
{
    global $db2conn;
    //    $rpage = $_SERVER['PHP_SELF'];
//    $pos = strlen(path);
//    $page = substr($rpage, $pos+1);
//    $pos = strpos($page, '.php');
//    $page = substr($page, 0, $pos);
    $rtree = substr($_SERVER['REQUEST_URI'], 1);
    $tree = explode('/', $rtree);
    $page = $tree[1];
//    echo '2'.$tree[1];
//    echo '3'.$tree[2];
    switch ($page)
    {
        case 'detail':
            $rpage = $_SERVER['HTTP_REFERER'];
            $pos = strpos($rpage, path);
            $pos2 = strlen(path);
            $page = substr($rpage, $pos+$pos2+1);
            $pos = strpos($page, '/');
            $page = substr($page, 0, $pos);
            echo '<a href="'.path.'/catalog">Home</a> / <a href="'.$rpage.'">'.ucfirst($page).'</a> / ';
            break;
        case 'products':
            $rpage = $_SERVER['HTTP_REFERER'];
            $pos = strpos($rpage, path);
            $pos2 = strlen(path);
            $page = substr($rpage, $pos+$pos2+1);
            $pos = strpos($page, '/');
            $page = substr($page, 0, $pos);
            $cdes[1] = $tree[2];
            $cdes[2] = $tree[3];
            $cdes[3] = $tree[4];
            echo '<a href="'.path.'/catalog">Home</a> / <a href="'.$rpage.'">'.ucfirst($page).'</a> / <b>'.dspBkDesc($cdes, '3').'</b>';
            break;
        case 'catalog':
            if  ($tree[4] <> '' )
                echo '<a href="'.path.'/catalog/">Home</a> / <a href="">'.ucfirst($page).'</a>';
            else if  ($tree[3] <> '' )
                echo '<a href="'.path.'/catalog/">Home</a> / <a href="'.path.'/'.$tree[1].'/">'.ucfirst($page).'</a> / <a href="'.path.'/'.$tree[1].'/'.$tree[2].'/">'.dspWbcDesc($tree[2], 'C').'</a> / '.dspWbcDesc($tree[3], 'C');
            else if  ($tree[2] <> '' )
                echo '<a href="'.path.'/catalog/">Home</a> / <a href="'.path.'/'.$tree[1].'/">'.ucfirst($page).'</a> / '.dspWbcDesc($tree[2], 'C');
            else if  ($tree[1] <> '' )
                echo '<a href="'.path.'/catalog/">Home</a> / '.ucfirst($page);
            break;
        default:
//            echo '<a href="http://www.wcpsolutions.com/home/">Home</a> / <a href="'.$rpage.'">'.ucfirst($page).'</a> /';
            break;
    }
}

/*****************************
 Add Sort to Search
 *****************************/
function addSort()
{
    // Add Sort Order to Query
    if(isset($_REQUEST['sort']))
    {
        $sort = $_REQUEST['sort'];
        switch($sort)
        {
            case 'Mfg Number':
                return " order by IMMFNO";
                break;

            case 'Item Number':
                return " order by IMITNO";
                break;

            case 'Product Desc':
                return " order by IMITD1";
                break;

            case 'HighLow':
                return " order by IMLPR1 desc";
                break;

            case 'LowHigh':
                return " order by IMLPR1 asc";
                break;

            default:
                return " order by IMITCL, IMITSC, IMITNO";
                break;
        }
    }
    else
    {
        return " order by IMITCL, IMITSC, IMITD1";
    }
}

/*****************************
 Add Filters to Search
 *****************************/
function addFilt()
{
    if(isset($_REQUEST['fv']) && !empty($_REQUEST['fv']))
    {
//        $Ltitle .= trim($_REQUEST['Ltitle']);
//        $Lfilter .= trim($_REQUEST['Lfilter']);
//        $filts = explode('+', $Lfilter);
//        $titls = explode('+', $Ltitle);
        $fv = array();
        $fh = array();
        $fh = explode('!', $_REQUEST['fh']);
//        $fh = array($_REQUEST['fh']);
        $fv = explode('!', str_replace('_', '/', $_REQUEST['fv']));
//        $fv = array(str_replace('_', '/', $_REQUEST['fv']));
        foreach ($fv as $k => $v)
        {
            $v = str_replace('$', '&', $v);
            if ( !empty($v) )
            {
                switch (trim($fh[$k]))
                {
                    case 'Item Type':
                    if ($v == 'Printing')
                        $fclause .= " and IMITGL = '10'";
                    else
                        $fclause .= " and IMITGL <> '10'";
                    break;

                    case 'Manufacturer':
                    $fclause .= " and IXMFGN = '" .db2_escape_string($v). "'";
                    break;

                    case 'Grade':
                    $fclause .= " and IXGRAD = '" .db2_escape_string($v). "'";
                    break;

                    case 'Brand':
                    $fclause .= " and IXGRAD = '" .db2_escape_string($v). "'";
                    break;

                    case 'Core Description':
                    $fclause .= " and IXCORD = '" .db2_escape_string($v). "'";
                    break;

                    case 'Color':
                    $fclause .= " and IXGCOL = '" .db2_escape_string($v). "'";
                    break;

                    case 'Size':
                    $fclause .= " and IXSZTX = '" .db2_escape_string($v). "'";
                    break;

                    case 'Size Number':
                    $fclause .= " and IXSZNM = '" .db2_escape_string($v). "'";
                    break;

                    case 'PCW':
                    $fclause .= " and IXPCWC = " .db2_escape_string($v);
                    break;

                    case 'Recycled Content':
                    $fclause .= " and IXGN01 = " .db2_escape_string($v);
                    break;

                    case 'Acid Free':
                    $fclause .= " and IXACID = '" .db2_escape_string($v). "'";
                    break;

                    default:
                    $title = $titls[$k];
                    $filter = $v;
                    $fclause = $fclause . "";
                    break;
                }
            }
        }
    }
    return $fclause;

}

/*****************************
 Remove Filters to Search
 *****************************/
function rmvFilt($spage, $fv, $fh, $sort)
{
    global $db2conn;
    
    if (! empty($fv)) 
    {
        $ofv = implode('!', $fv);
        $ofh = implode('!', $fh);
        $spage = str_replace(' ', '+', $spage);
        echo '<p>';
        foreach ($fv as $k => $v) {
            if (! empty($v)) 
            {
                if ($fh[$k] == 'Manufacturer2') 
                {
                    $query = "select PVVNNM from " . FILELIB . "/VENDR where PVVNNO = ?";
                    $stmt = db2_prepare($db2conn, $query);
                    if (! ($stmt)) 
                    {
                        db2_close($db2conn);
                        die("<b>ErrorVN " . db2_stmt_error() . ":" . db2_stmt_errormsg() . "</b>");
                    }
                    db2_bind_param($stmt, 1, "v", DB2_PARAM_IN);
                    db2_execute($stmt);
                    if ($row = db2_fetch_assoc($stmt)) 
                    {
                        if ($k == 0) 
                        {
                            $xfh = str_replace($fh[$k], '', $ofh);
                            $xfv = str_replace($v, '', $ofv);
                        } 
                        else 
                        {
                            $xfh = str_replace('!' . $fh[$k], '', $ofh);
                            $xfv = str_replace('!' . $v, '', $ofv);
                        }
                        $v = str_replace('$', '&', $v);
                        if (empty($xfh))
                            echo '<a class="btn btn-xs btn-success" href="' . $spage . '/1/' . urlencode($sort) . '" role="button">' . $row['PVVNNM'] . ' <i class="glyphicon glyphicon-remove-sign"></i></a>&nbsp;';
                        else
                            echo '<a class="btn btn-xs btn-success" href="' . $spage . '/1/' . urlencode($sort) . '/' . urlencode($xfh) . '/' . urlencode($xfv) . '" role="button">' . $row['PVVNNM'] . ' <i class="glyphicon glyphicon-remove-sign"></i></a>&nbsp;';
                    }
                } 
                else 
                {
                    if ($k == 0) 
                    {
                        $xfh = str_replace($fh[$k], '', $ofh);
                        $xfv = str_replace($v, '', $ofv);
                    } 
                    else 
                    {
                        $xfh = str_replace('!' . $fh[$k], '', $ofh);
                        $xfv = str_replace('!' . $v, '', $ofv);
                    }
                    $v = str_replace('$', '&', $v);
                    if (empty($xfh))
                        echo '<a class="btn btn-xs btn-success" href="' . $spage . '/1/' . urlencode($sort) . '" role="button">' . $v . ' <i class="glyphicon glyphicon-remove-sign"></i></a>&nbsp;';
                    else
                        echo '<a class="btn btn-xs btn-success" href="' . $spage . '/1/' . urlencode($sort) . '/' . urlencode($xfh) . '/' . urlencode($xfv) . '" role="button">' . $v . ' <i class="glyphicon glyphicon-remove-sign"></i></a>&nbsp;';
                }
            }
        }
        echo '</p>';
    }
}

/*************************************************
 Check to see if the Item should show in the list
 *************************************************/
function showItem($ltype, $stmtW, &$a_itms, &$a_filt, $whid)
{
    global $db2conn;
    
    $rows = 0;
    $whid = 'P';
    while ( $row = db2_fetch_assoc($stmtW) )
    {
        $show = 'N';
        $query = "select IBITNO, IBFCST, IBVNNO, PBITN, IXCLOI, IXGRAD, IXCORD, IXGCOL, IXSZTX, IXSZNM, IXMFGN, IXPCWC, IXGN01, IXACID from ".FILELIB."/ITBAL left join ".FILELIB."/ITMSX on IBITNO = IXITNO left join ".FILELIB."/PBITNO on IBITNO = PBITN where IBITNO = ? and IBWHID = ?";
    	$stmt = db2_prepare($db2conn, $query);
    	if (!($stmt))
    	{
    	    db2_close($db2conn);
    	    die("<b>Error3 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
    	}
        $item = $row['IMITNO'];
    	db2_bind_param($stmt, 1, "item", DB2_PARAM_IN);
    	db2_bind_param($stmt, 2, "whid", DB2_PARAM_IN);	
    	if (db2_execute($stmt)) 
    	{
    	   	$rowB = db2_fetch_assoc($stmt);
            if ( $rowB['IBITNO'] == '' )
                $show = 'N';
            else if ( $rowB['IBFCST'] <> 'Y' )
                $show = 'N';
            else if ( $rowB['IXCLOI'] == 'Y' )
                $show = 'N';
    	    else if ( $rowB['PBITN'] == '' )
                $show = 'N';
    	    else 
    	        $show = 'Y';
    	}
    	if ( $show == 'Y' )
        {
            $a_itms[$row['IMITNO']]['NUM'] = trim($row['IMITNO']);
    		$a_itms[$row['IMITNO']]['D1'] = $row['IMITD1'];
       		$a_itms[$row['IMITNO']]['D2'] = $row['IMITD2'];
    		$a_itms[$row['IMITNO']]['UMS'] = $row['IMPRUM'];
    		$a_itms[$row['IMITNO']]['PRC'] = number_format($row['IMLPR1'], 2);
    		$a_itms[$row['IMITNO']]['MFN'] = $row['IMMFNO'];
    		if ( trim($row['IMITGL']) == '10' ) 
    		     $a_filt[0]['Item Type']['Printing'] = 'Printing';
    		else
    		     $a_filt[0]['Item Type']['Industrial'] = 'Industrial';
    		if ( trim($rowB['IXCORD']) <> '' ) $a_filt[1]['Core Description'][trim($rowB['IXCORD'])] = trim($rowB['IXCORD']);
    		if ( trim($rowB['IXMFGN']) <> '' ) $a_filt[2]['Manufacturer'][trim($rowB['IXMFGN'])] = trim($rowB['IXMFGN']);
    		if ( trim($rowB['IXGRAD']) <> '' && trim($row['IMITGL']) == '10' ) $a_filt[3]['Grade'][trim($rowB['IXGRAD'])] = trim($rowB['IXGRAD']);
    		if ( trim($rowB['IXGRAD']) <> '' && trim($row['IMITGL']) <> '10') $a_filt[4]['Brand'][trim($rowB['IXGRAD'])] = trim($rowB['IXGRAD']);
    		if ( trim($rowB['IXGCOL']) <> '' ) $a_filt[5]['Color'][trim($rowB['IXGCOL'])] = trim($rowB['IXGCOL']);
    		if ( trim($rowB['IXSZTX']) <> '' ) $a_filt[6]['Size'][trim($rowB['IXSZTX'])] = trim($rowB['IXSZTX']);
    		if ( trim($rowB['IXSZNM']) <> '' )  $a_filt[7]['Size Number'][trim($rowB['IXSZNM'])] = trim($rowB['IXSZNM']);
    		if ( trim($rowB['IXPCWC']) > 0 )  $a_filt[8]['PCW'][number_format($rowB['IXPCWC'], 0)] = number_format($rowB['IXPCWC'], 0);
    		if ( trim($rowB['IXGN01']) > 0 )  $a_filt[9]['Recycled Content'][number_format($rowB['IXGN01'], 0)] = number_format($rowB['IXGN01'], 0);
    		if ( trim($rowB['IXACID']) <> '' )  $a_filt[10]['Acid Free'][trim($rowB['IXACID'])] = trim($rowB['IXACID']);
    		$rows++;
        }
    }
    return $rows;
}

/*************************************************
 Display array of items in div form
 *************************************************/
function divItem($a_itms, $index, $nx)
{
        $i = 0;
        $rows = 0;
        foreach ($a_itms as $k => $v) 
    	{
    	    $rows++;
    		// Format image for thumbnail output
//    		$imgsrc = '<img src="/Strategi/images/250x250/' . trim($a_itms[$k]['NUM']) . '.jpg" class="img-zoom" onerror="imgError(this);">';
    		$imgsrc = '<img src="'.path.'/images/nophoto.gif" lsrc="/Strategi/images/250x250/' . trim($a_itms[$k]['NUM']) . '.jpg" class="img-list" onerror="imgError(this);">';
    		if ($rows >= $index && $rows <= $nx)
    	   	{
        		echo '<div class="row-fluid itmtab">';
                echo '<div class="span3"><a href="'.path.'/detail/'.$a_itms[$k]['NUM'].'/">'.$imgsrc.'</a></div>';
                echo '<div class="span6"><h6><a href="'.path.'/detail/'.$a_itms[$k]['NUM'].'/">'.$a_itms[$k]['D1'].$a_itms[$k]['D2'].'</a><br />Item #: '.trim($a_itms[$k]['NUM']).'<br />Manf #: '.trim($a_itms[$k]['MFN']).'</h6>';
                echo '<a href="#" id="showm'.trim($a_itms[$k]['NUM']).'" class="showm" rel="'.trim($a_itms[$k]['NUM']).'">Show more...</a>';
                echo '<div id="showc'.trim($a_itms[$k]['NUM']).'" class="showc"></div>';
                echo '<a href="#" id="showl'.trim($a_itms[$k]['NUM']).'" class="showl" rel="'.trim($a_itms[$k]['NUM']).'">Show less</a></div>';
                echo '<div class="span3"><h6>$ '.$a_itms[$k]['PRC'].' / '.$a_itms[$k]['UMS'].'<br /><br />';
  			    echo '<input type="text" id="q'. trim($a_itms[$k]['NUM']). '" class="qqty input-sm" pattern="[0-9]*"> ';
  		    	echo '<a href="'.path.'/cart_action.php?task=add&item='. trim($a_itms[$k]['NUM']). '&num='.$i.'&qty=" class="addi btn btn-sm btn-info" alt="q'. trim($a_itms[$k]['NUM']). '">Add</a></h6>';
		        echo '</div>';
                echo '</div>';    		
		//    	if ((($rows / MAX_DSP_CAT_PER_ROW) == floor($rows / MAX_DSP_CAT_PER_ROW)) && ($rows != $max_prods)) 
        //    	{
        //        	echo '      </div>' . "\n";
        //        	echo '      <div class="row-fluid">' . "\n";
        //		}
    		    $i += 1;
    	   	}
    	}
}

/*************************************************
 Display array of items in table form
 *************************************************/
function tblItem($a_itms, $ditm, $typ, $sel)
{
       	$i = 0;
        $rows = 0;
        $cde4 = '';
        $grd = false;
        $cor = false;
        $clr = false;
        $szd = false;
        foreach ($a_itms as $k => $v) 
    	{
            if (array_key_exists('GRD', $v)) $grd = true;
            if (array_key_exists('COR', $v)) $cor = true;
            if (array_key_exists('CLR', $v)) $clr = true;
            if (array_key_exists('SZD', $v)) $szd = true;
    	}
        foreach ($a_itms as $k => $v) 
    	{
    	    $rows++;
    		// Format image for thumbnail output
    		$imgsrc = '<img src="/Strategi/images/250x250/' . trim($a_itms[$k]['NUM']) . '.jpg" class="img-listd" onerror="imgError(this);">';
    	   	$codes = getBook($a_itms[$k]['NUM']);
            if ( empty($sel) || $codes[$typ][4] == $sel )
    	   	{
    	   	    if ($cde4 <> $codes[$typ][4])
                {
                    $cde4 = $codes[$typ][4];
                    $cdes = $codes[$typ];
                    $head .= '<li class="active"><a href="#tab'.$rows.'" data-toggle="tab">'.dspBkDesc($cdes, '4').'</a></li>';
                    $content .= '<div class="tab-content">';
                    $content .= '<div class="tab-pane active" id="tab'.$rows.'">';
                    $content .= '<table class="table table-striped table-bordered table-condensed">';
                }
                if (trim($a_itms[$k]['NUM']) <> $ditm)
                    $content .= '<tr>';
                else
                    $content .= '<tr class="info">';
                $content .= '<td><a href="'.path.'/detail/'.$a_itms[$k]['NUM'].'/">'.$imgsrc.'</a></td>';
                $content .= '<td><a href="'.path.'/detail/'.$a_itms[$k]['NUM'].'/"><h6 class="itmd">'.trim($a_itms[$k]['NUM']).'</h6></a>';
                if ( trim($a_itms[$k]['MFN']) <> '' )
                    $content .= '<h6 class="itmtd">Mnf: '.trim($a_itms[$k]['MFN']).'</h6>';
                $content .= '</td>';
                $content .= '<td>'.$a_itms[$k]['D1'].$a_itms[$k]['D2'].'</td>';
                if ($grd)
                    $content .= '<td>'.$a_itms[$k]['GRD'].'</td>';
                if ($cor)
                    $content .= '<td>'.$a_itms[$k]['COR'].'</td>';
                if ($clr)
                    $content .= '<td>'.$a_itms[$k]['CLR'].'</td>';
                if ($szd)
                $content .= '<td>'.$a_itms[$k]['SZD'].'</td>';
		        $content .= '<td>'.number_format($a_itms[$k]['PRC'], '2').'/'. $a_itms[$k]['PUM'].'</td>';
  			    $content .= '<td><p  class="text-center"><input type="text" id="q'. trim($a_itms[$k]['NUM']). '" class="qqty input-sm" pattern="[0-9]*"> </p><p class="text-center">';
  		    	$content .= '<a href="'.path.'/cart_action.php?task=add&item='. trim($a_itms[$k]['NUM']). '&num='.$i.'&qty=" class="addi btn btn-xs btn-info" alt="q'. trim($a_itms[$k]['NUM']). '">Add</a></p></td>';
  		    	$content .= '</tr>';    		
  		    	$i += 1;
    	   	}
    	}
    	$content .= '</table></div>';
    	echo '<ul class="nav nav-tabs">';
        $head .= '<li><a href="#tabR" data-toggle="tab">Related Items</a></li>';
    	echo $head;
    	echo '</ul>';
    	echo $content;
}

/*************************************************
 Display array of items in table form
 *************************************************/
function tblRel($a_itms, $ditm, $index, $nx, $typ)
{
       	$i = 0;
        $rows = 0;
        $cde4 = '';
        echo '<div class="tab-pane" id="tabR">';
        echo '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';
        foreach ($a_itms as $k => $v) 
    	{
    	    $rows++;
    		// Format image for thumbnail output
    		$imgsrc = '<img src="/Strategi/images/250x250/' . trim($a_itms[$k]['NUM']) . '.jpg" class="img-listd" onerror="imgError(this);">';
    		if ($rows >= $index && $rows <= $nx)
    	   	{
    	   	    $codes = getBook($a_itms[$k]['NUM']);
    	   	    if ($cde4 <> $codes[$typ][4])
                {
                    $cde4 = $codes[$typ][4];
                    $cdes = $codes[$typ];
                    if ($rows > 1)
                        $content .= '</table></div></div></div>';
                    //                    $content .= '<li><a href="#tab'.$rows.'" data-toggle="tab">'.dspBkDesc($cdes, '4').'</a></li>';
                    $content .= '<div class="panel panel-default">
                                    <div class="panel-heading" role="tab" id="heading'.$rows.'" data-toggle="collapse" data-target="#collapse'.$rows.'">
                                        <h5>'.dspBkDesc($cdes, '4').'</h5>
                                    </div>';
//                    $content .= '<div class="">';
                    $content .= '<div id="collapse'.$rows.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading'.$rows.'">
                            <div class="panel-body">';
                    $content .= '<table class="table table-striped table-bordered table-condensed">';
                }
                if (trim($a_itms[$k]['NUM']) <> $ditm)
                    $content .= '<tr>';
                else
                    $content .= '<tr class="info">';
                $content .= '<td><a href="'.path.'/detail/'.$a_itms[$k]['NUM'].'/">'.$imgsrc.'</a></td>';
                $content .= '<td><h6 class="itmd"><a href="'.path.'/detail/'.$a_itms[$k]['NUM'].'/">'.trim($a_itms[$k]['NUM']).'</a></h6>';
                if ( trim($a_itms[$k]['MFN']) <> '' )
                    $content .= '<h6 class="itmtd"><a href="'.path.'/detail/'.$a_itms[$k]['NUM'].'/">Mnf: '.trim($a_itms[$k]['MFN']).'</a></h6>';
                $content .= '</td>';
                $content .= '<td><a href="'.path.'/detail/'.$a_itms[$k]['NUM'].'/">'.$a_itms[$k]['D1'].$a_itms[$k]['D2'].'</a></td>';
		        $content .= '<td>'.$a_itms[$k]['GRD'].'</td>';
		        $content .= '<td>'.$a_itms[$k]['COR'].'</td>';
		        $content .= '<td>'.$a_itms[$k]['CLR'].'</td>';
		        $content .= '<td>'.$a_itms[$k]['SZD'].'</td>';
		        $content .= '<td>'.number_format($a_itms[$k]['PRC'], '2').'/'. $a_itms[$k]['PUM'].'</td>';
  			    $content .= '<td><p  class="text-center"><input type="text" id="q'. trim($a_itms[$k]['NUM']). '" class="qqty input-sm" pattern="[0-9]*"> </p><p class="text-center">';
  		    	$content .= '<a href="'.path.'/cart_action.php?task=add&item='. trim($a_itms[$k]['NUM']). '&num='.$i.'&qty=" class="addi btn btn-xs btn-info" alt="q'. trim($a_itms[$k]['NUM']). '">Add</a></p></td>';
  		    	$content .= '</tr>';    		
    		    $i += 1;
    	   	}
    	}
        $content .= '</table></div></div></div>';
    	echo $content;
}

/*************************************************
 Display array of items in table form
 *************************************************/
function relItems($a_itms, $index, $nx, $typ)
{
       	$i = 0;
        $rows = 0;
        $cde4 = '';
        foreach ($a_itms as $k => $v) 
    	{
    	    $rows++;
    		if ($rows >= $index && $rows <= $nx)
    	   	{
    	   	    $codes = getBook($a_itms[$k]['NUM']);
    	   	    if ($cde4 <> $codes[$typ][4])
                {
                    $cde4 = $codes[$typ][4];
                    $cdes = $codes[$typ];
                    if ($rows == 1)
                    {
                        echo '<li class="list-group-item disabled"><a href="'.path.'/products/'.$cdes[1].'/'.$cdes[2].'/'.$cdes[3].'/1/Product+Desc" class="small">'.dspBkDesc($cdes, '4').'</a></li>';
                    }
                    else
                    {
                        echo '<li class="list-group-item"><a href="'.path.'/products/'.$cdes[1].'/'.$cdes[2].'/'.$cdes[3].'/1/Product+Desc" class="small">'.dspBkDesc($cdes, '4').'</a></li>';
                    }
                }
    		    $i += 1;
    	   	}
    	}
}

/*************************************************
 Display array of items in table form
 *************************************************/
function cmpitems($item, $db2conn)
{
    $query = "select MRRPIT, IMITD1, IMITD2, IMUNM1, IMPRUM, IMLPR1, IMMFNO, IXCLOI, IXGRAD, IXCORD, IXGCOL, IXSZTX from ".FILELIB."/IMXRF left join ".FILELIB."/ITMST on MRRPIT = IMITNO left join ".FILELIB."/ITMSX on IMITNO = IXITNO where MRRPCD = 'C' and MRORIT = ?";

    $stmt = db2_prepare($db2conn, $query);
	if (!($stmt))
	{
	    db2_close($db2conn);
	    die("<b>Error1 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
	}
	
	db2_bind_param($stmt, 1, "item", DB2_PARAM_IN);
	db2_execute($stmt);

//  	$content .= '<div class="panel panel-default">
//          <div class="panel-heading"><b><a data-toggle="collapse" href="#TCmpDet" aria-expanded="false" aria-controls="TCmpDet">Complimentary Items - Click to View</a></b></div>
//              <div id="TCmpDet" class="collapse"><table class="table table-striped table-condensed">';
    $head .= '<ul class="nav nav-tabs"><li class="active"><a href="#tabC" data-toggle="tab">Complimentary Items</a></li></ul>';
    $content .= '<div class="tab-content">';
    $content .= '<div class="tab-pane active" id="tabC">';
    $content .= '<table class="table table-striped table-bordered table-condensed">';
    
    $show = 'N';
    $grd = false;
    $cor = false;
    $clr = false;
    $szd = false;
    $first = true;
    $i = 0;
    while($row = db2_fetch_both($stmt))
    {
        if ($first)
        {
            if (trim($row['IXGRAD']) <> '') $grd = true;
            if (trim($row['IXCORD']) <> '') $cor = true;
            if (trim($row['IXGCOL']) <> '') $clr = true;
            if (trim($row['IXSZTX']) <> '') $szd = true;
            $first = false;
        }
        $imgsrc = '<img src="/Strategi/images/250x250/' . trim($row['MRRPIT']) . '.jpg" class="img-listd" onerror="imgError(this);">';
        $content .= '<tr>
                      <td><a href="/wcp/detail/'.$row['MRRPIT'].'">'.$imgsrc.'</a></td>
    		          <td><h6 class="itmd"><a href="/wcp/detail/'.$row['MRRPIT'].'">'.trim($row['MRRPIT']).'</a></h6>';
                if ( trim($row['IMMFNO']) <> '' )
                    $content .= '<h6 class="itmtd"><a href="/wcp/detail/'.$row['MRRPIT'].'">Mnf: '.trim($row['IMMFNO']).'</a></h6>';
    		        $content .=  '</td><td><a href="/wcp/detail/'.$row['MRRPIT'].'">'.$row['IMITD1'].$row['IMITD2'].'</a></td>';
                if ($grd)
                    $content .= '<td>'.$row['IXGRAD'].'</td>';
                if ($cor)
                    $content .= '<td>'.$row['IXCORD'].'</td>';
                if ($clr)
                    $content .= '<td>'.$row['IXGCOL'].'</td>';
                if ($szd)
                    $content .= '<td>'.$row['IXSZTX'].'</td>';
		        $content .= '<td>'.number_format($row['IMLPR1'], '2').'/'. $row['IMPRUM'].'</td>';
  			    $content .= '<td><p  class="text-center"><input type="text" id="q'. trim($row['MRRPIT']). '" class="qqty input-sm" pattern="[0-9]*"> </p><p class="text-center">';
  		    	$content .= '<a href="'.path.'/cart_action.php?task=add&item='. trim($row['MRRPIT']). '&num='.$i.'&qty=" class="addi btn btn-xs btn-info" alt="q'. trim($row['MRRPIT']). '">Add</a></p></td>
    		    </tr>';
        $show = 'Y';
  	   	$i += 1;
    }
    	$content .= '</table></div>';
        if ($show == 'Y')
            echo $head.$content;    
}

/*************************************************
 Calculate minimum multiplier
 *************************************************/
function calcMinMult($qty, $mult, $msg)
{
    $rmndr = $qty%$mult;
    if ( $rmndr > 0 )
    {
        $msg = 'Item had a minimum multiplier of '.$mult.' and has been increased.';
        $add = $mult - $rmndr;
        return $add;
    }
    else
        return 0;
}

/*************************************************
 Get Book Codes for an Item in the Price Catalog
 *************************************************/
function getBook($item)
{
    global $db2conn;

    $codes = array();
    $query = "select PBCD1, PBCD2, PBCD3, PBCD4 from ".FILELIB."/PBITNO where PBITN = ? and PBCD1 = ?";
    $stmt = db2_prepare($db2conn, $query);
    if (!($stmt))
    {
        db2_close($db2conn);
        die("<b>Error3 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
    }
    // Industrial
    $cde1 = 'I1';
    db2_bind_param($stmt, 1, "item", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "cde1", DB2_PARAM_IN);    
   	if (db2_execute($stmt)) 
    {
        $row = db2_fetch_assoc($stmt);
        $codes['I'][1] = $row['PBCD1'];
        $codes['I'][2] = $row['PBCD2'];
        $codes['I'][3] = $row['PBCD3'];
        $codes['I'][4] = $row['PBCD4'];
    }
    // Print
    $cde1 = 'P1';
    db2_bind_param($stmt, 1, "item", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "cde1", DB2_PARAM_IN);    
   	if (db2_execute($stmt)) 
    {
        $row = db2_fetch_assoc($stmt);
        $codes['P'][1] = $row['PBCD1'];
        $codes['P'][2] = $row['PBCD2'];
        $codes['P'][3] = $row['PBCD3'];
        $codes['P'][4] = $row['PBCD4'];
    }
    return $codes;
}

/*************************************************
 Print Book Codes Text
 *************************************************/
function dspBkDesc($codes, $lvl)
{
    global $db2conn;

    if ($lvl == '3')
    {
        $query = "select P3BHA, P3BHB, P3BHC from ".FILELIB."/PBC003 where P3CD1 = ? and P3CD2 = ? and P3CD3 = ?";
        $stmt = db2_prepare($db2conn, $query);
        if (!($stmt))
        {
            db2_close($db2conn);
            die("<b>Error3 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
        }
        $cde1 = $codes[1];
        $cde2 = $codes[2];
        $cde3 = $codes[3];
        db2_bind_param($stmt, 1, "cde1", DB2_PARAM_IN);
        db2_bind_param($stmt, 2, "cde2", DB2_PARAM_IN);    
        db2_bind_param($stmt, 3, "cde3", DB2_PARAM_IN);    
        if (db2_execute($stmt)) 
        {
            $row = db2_fetch_assoc($stmt);
            $text = $row['P3BHA'].$row['P3BHB'].$row['P3BHC'];
            return $text;
        }
    }
    if ($lvl == '4')
    {
        $query = "select P4IHA from ".FILELIB."/PBC004 where P4CD1 = ? and P4CD2 = ? and P4CD3 = ? and P4CD4 = ?";
        $stmt = db2_prepare($db2conn, $query);
        if (!($stmt))
        {
            db2_close($db2conn);
            die("<b>Error3 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
        }
        $cde1 = $codes[1];
        $cde2 = $codes[2];
        $cde3 = $codes[3];
        $cde4 = $codes[4];
        db2_bind_param($stmt, 1, "cde1", DB2_PARAM_IN);
        db2_bind_param($stmt, 2, "cde2", DB2_PARAM_IN);    
        db2_bind_param($stmt, 3, "cde3", DB2_PARAM_IN);    
        db2_bind_param($stmt, 4, "cde4", DB2_PARAM_IN);    
        if (db2_execute($stmt)) 
        {
            $row = db2_fetch_assoc($stmt);
            $text = $row['P4IHA'];
            return $text;
        }
    }
}

/*************************************************
 Print Web Codes Text
 *************************************************/
function dspWbcDesc($code, $lvl)
{
    global $db2conn;

    if ($lvl == 'C')
    {
        $query = "select WEBDSC from WCPWEB/WEBCATSV where WBPCD2 = ? and CATSTAT = 'Y'";
        $stmt = db2_prepare($db2conn, $query);
        if (!($stmt))
        {
            db2_close($db2conn);
            die("<b>Error3 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
        }
        $cde1 = $code;
        db2_bind_param($stmt, 1, "cde1", DB2_PARAM_IN);
        if (db2_execute($stmt)) 
        {
            $row = db2_fetch_assoc($stmt);
            $text = $row['WEBDSC'];
            return $text;
        }
    }
}

/*************************************************
 Get Book Codes for an Item in the Price Catalog
 *************************************************/
function getBookItm($cdes, &$a_itms, $whid)
{
    global $db2conn;
    
    $rows = 0;
    $query = "select PBITN, PBTX1, PBTX2, PBTX3, PBTX4, PBTX5, PBTX6, PBTX7, PBTX8, PBTX9, PBTX10 from ".FILELIB."/PBITNO where PBCD1 = ? and PBCD2 = ? and PBCD3 = ?";
    $stmt = db2_prepare($db2conn, $query);
    if (!($stmt))
    {
        db2_close($db2conn);
        die("<b>ErrorPC ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
    }
    $cde1 = $cdes[1];
    $cde2 = $cdes[2];
    $cde3 = $cdes[3];
    db2_bind_param($stmt, 1, "cde1", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "cde2", DB2_PARAM_IN);    
    db2_bind_param($stmt, 3, "cde3", DB2_PARAM_IN);    
    db2_execute($stmt);
    while ( $row = db2_fetch_assoc($stmt) )
    {
        $show = 'N';
        $query = "select IMITNO, IMITD1, IMITD2, IMUNM1, IMPRUM, IMLPR1, IMMFNO, IBITNO, IBFCST, IXCLOI, IXGRAD, IXCORD, IXGCOL, IXSZTX  from ".FILELIB."/ITBAL left join ".FILELIB."/ITMSX on IBITNO = IXITNO left join ".FILELIB."/ITMST on IBITNO = IMITNO where IBITNO = ? and IBWHID = ?";
    	$stmtB = db2_prepare($db2conn, $query);
    	if (!($stmtB))
    	{
    	    db2_close($db2conn);
    	    die("<b>Error3 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
    	}
        $item = $row['PBITN'];
    	db2_bind_param($stmtB, 1, "item", DB2_PARAM_IN);
    	db2_bind_param($stmtB, 2, "whid", DB2_PARAM_IN);	
    	if (db2_execute($stmtB)) 
    	{
    	   	$rowB = db2_fetch_assoc($stmtB);
            if ( $rowB['IBITNO'] == '' )
                $show = 'N';
            else if ( $rowB['IBFCST'] <> 'Y' )
                $show = 'N';
            else if ( $rowB['IXCLOI'] == 'Y' )
                $show = 'N';
    	    else 
    	        $show = 'Y';
    	}
        if ( $show == 'Y' )
        {
            $a_itms[$rowB['IMITNO']]['NUM'] = trim($rowB['IMITNO']);
    		$a_itms[$rowB['IMITNO']]['D1'] = $rowB['IMITD1'];
       		$a_itms[$rowB['IMITNO']]['D2'] = $rowB['IMITD2'];
    		$a_itms[$rowB['IMITNO']]['UMS'] = $rowB['IMUNM1'];
    		$a_itms[$rowB['IMITNO']]['PUM'] = $rowB['IMPRUM'];
    		$a_itms[$rowB['IMITNO']]['PRC'] = number_format($rowB['IMLPR1'], 2);
    		$a_itms[$rowB['IMITNO']]['MFN'] = $rowB['IMMFNO'];
            if ( !empty(trim($rowB['IXGRAD'])) ) 
        		$a_itms[$rowB['IMITNO']]['GRD'] = $rowB['IXGRAD'];
            if ( !empty(trim($rowB['IXGCOL'])) ) 
                $a_itms[$rowB['IMITNO']]['CLR'] = $rowB['IXGCOL'];
            if ( !empty(trim($rowB['IXCORD'])) ) 
                $a_itms[$rowB['IMITNO']]['COR'] = $rowB['IXCORD'];
            if ( !empty(trim($rowB['IXSZTX'])) ) 
                $a_itms[$rowB['IMITNO']]['SZD'] = $rowB['IXSZTX'];
    		$a_itms[$rowB['IMITNO']]['TX1'] = $row['PBTX1'];
    		$a_itms[$rowB['IMITNO']]['TX2'] = $row['PBTX2'];
    		$a_itms[$rowB['IMITNO']]['TX3'] = $row['PBTX3'];
    		$a_itms[$rowB['IMITNO']]['TX4'] = $row['PBTX4'];
       		$a_itms[$rowB['IMITNO']]['TX5'] = $row['PBTX5'];
    		$a_itms[$rowB['IMITNO']]['TX6'] = $row['PBTX6'];
    		$a_itms[$rowB['IMITNO']]['TX7'] = $row['PBTX7'];
    		$a_itms[$rowB['IMITNO']]['TX8'] = $row['PBTX8'];
    		$a_itms[$rowB['IMITNO']]['TX9'] = $row['PBTX9'];
       		$a_itms[$rowB['IMITNO']]['TX10'] = $row['PBTX10'];
       		$rows++;
        }
    }
    return $rows;
}

/*************************************************
 Get Book Codes for an Item in the Price Catalog
 *************************************************/
function getExtra($item, $whid)
{
    global $db2conn;
    
    $query = "select IMITNO, IMITGL, IMMFNO, IBITNO, IXCLOI, IXGRAD, IXCORD, IXGCOL, IXSZTX, IXMFGN, IXMWGT, IXCALI, IXGDIR, IXBWGT, IXFNSH, IXBRIT, IXOPAC, IXPPI, IXPCWC, IXGLOS, IXACID, IXLCMP, IXSHFL, IXGN01, IXGA01, IXGA02, IXGA03 from ".FILELIB."/ITBAL left join ".FILELIB."/ITMSX on IBITNO = IXITNO left join ".FILELIB."/ITMST on IBITNO = IMITNO where IBITNO = ? and IBWHID = ?";
    $stmtB = db2_prepare($db2conn, $query);
  	if (!($stmtB))
   	{
   	    db2_close($db2conn);
   	    die("<b>Error3 ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>");
   	}
   	db2_bind_param($stmtB, 1, "item", DB2_PARAM_IN);
   	db2_bind_param($stmtB, 2, "whid", DB2_PARAM_IN);	
    db2_execute($stmtB); 
   	$rowB = db2_fetch_assoc($stmtB);
   	$glcde = $rowB['IMITGL'];
   	if ( $glcde <> '10')
   	{
  	    echo '<div class="panel panel-default">
            <div class="panel-heading" data-toggle="collapse" data-target="#TAddDet"><b><a>Additional Details</a></b></div>
                 <div id="TAddDet" class="collapse in"><table class="table table-striped table-condensed">';
        if ( trim($rowB['IXMFGN']) <> '' ) echo '<tr><td class="col-md-4">Manufacturer</td><td class="col-md-6">'.$rowB['IXMFGN'].'</td></tr>';
  	    if ( trim($rowB['IXGRAD']) <> '' ) echo '<tr><td class="col-md-4">Brand</td><td class="col-md-6">'.$rowB['IXGRAD'].'</td></tr>';
        if ( trim($rowB['IXGCOL']) <> '' ) echo '<tr><td class="col-md-4">Color</td><td class="col-md-6">'.$rowB['IXGCOL'].'</td></tr>';
        if ( trim($rowB['IXCORD']) <> '' ) echo '<tr><td class="col-md-4">Core Desc.</td><td class="col-md-6">'.$rowB['IXCORD'].'</td></tr>';
        if ( trim($rowB['IXSZTX']) <> '' ) echo '<tr><td class="col-md-4">Size</td><td class="col-md-6">'.$rowB['IXSZTX'].'</td></tr>';
        echo '</table></div>
  	         </div>';
   	    if ( trim($rowB['IXPCWC']) > 0 || trim($rowB['IXGN01']) > 0 )
        {
            echo '<div class="panel panel-default">
      	      <div class="panel-heading" data-toggle="collapse" data-target="#TEnvDet"><a><b>Environmental - Click to Toggle</b></a></div>
                    <div id="TEnvDet" class="collapse"><table class="table table-striped table-condensed">';
            if ( trim($rowB['IXPCWC']) > 0 ) echo '<tr><td class="col-md-4">Post Consumer Waste</td><td class="col-md-6">'.number_format($rowB['IXPCWC'], 0).' %</td></tr>';
            if ( trim($rowB['IXGN01']) <> '' ) echo '<tr><td class="col-md-4">Recycled Content</td><td class="col-md-6">'.number_format($rowB['IXGN01'], 0).' %</td></tr>';
  	        echo '</table></div>
       	         </div>';
        }
   	}
    else 
    {
  	    echo '<div class="panel panel-default">
            <div class="panel-heading" data-toggle="collapse" data-target="#TAddDet"><a><b>Additional Details</b></a></div>
                <div id="TAddDet" class="collapse in"><table class="table table-striped table-condensed">';
        if ( trim($rowB['IXMFGN']) <> '' ) echo '<tr><td class="col-md-4">Manufacturer</td><td class="col-md-6">'.$rowB['IXMFGN'].'</td></tr>';
  	    if ( trim($rowB['IXGRAD']) <> '' ) echo '<tr><td class="col-md-4">Grade</td><td class="col-md-6">'.$rowB['IXGRAD'].'</td></tr>';
        if ( trim($rowB['IXGCOL']) <> '' ) echo '<tr><td class="col-md-4">Color</td><td class="col-md-6">'.$rowB['IXGCOL'].'</td></tr>';
        if ( trim($rowB['IXCORD']) <> '' ) echo '<tr><td class="col-md-4">Core Desc.</td><td class="col-md-6">'.$rowB['IXCORD'].'</td></tr>';
        if ( trim($rowB['IXSZTX']) <> '' ) echo '<tr><td class="col-md-4">Size</td><td class="col-md-6">'.$rowB['IXSZTX'].'</td></tr>';
        if ( $rowB['IXMWGT'] > 0 ) echo '<tr><td class="col-md-4">M-Weight</td><td class="col-md-6">'.$rowB['IXMWGT'].'</td></tr>';
  	    if ( $rowB['IXCALI'] > 0 ) echo '<tr><td class="col-md-4">Caliper</td><td class="col-md-6">'.$rowB['IXCALI'].'</td></tr>';
        if ( trim($rowB['IXGDIR']) <> '' ) echo '<tr><td class="col-md-4">Grain Direction</td><td class="col-md-6">'.$rowB['IXGDIR'].'</td></tr>';
        if ( $rowB['IXBWGT'] > 0 ) echo '<tr><td class="col-md-4">Basis Weight</td><td class="col-md-6">'.$rowB['IXBWGT'].'</td></tr>';
        if ( trim($rowB['IXFNSH']) <> '' ) echo '<tr><td class="col-md-4">Finish</td><td class="col-md-6">'.$rowB['IXFNSH'].'</td></tr>';
        if ( $rowB['IXBRIT'] > 0 ) echo '<tr><td class="col-md-4">Brightness</td><td class="col-md-6">'.$rowB['IXBRIT'].'</td></tr>';
  	    if ( $rowB['IXOPAC'] > 0 ) echo '<tr><td class="col-md-4">Opacity</td><td class="col-md-6">'.$rowB['IXOPAC'].'</td></tr>';
        if ( $rowB['IXPPI'] > 0 ) echo '<tr><td class="col-md-4">Pages Per Inch</td><td class="col-md-6">'.$rowB['IXPPI'].'</td></tr>';
        if ( $rowB['IXGLOS'] > 0 ) echo '<tr><td class="col-md-4">Gloss</td><td class="col-md-6">'.$rowB['IXGLOS'].'</td></tr>';
  	    if ( trim($rowB['IXLCMP']) <> '' ) echo '<tr><td class="col-md-4">Laser Compatible</td><td class="col-md-6">'.$rowB['IXLCMP'].'</td></tr>';
  	    if ( trim($rowB['IXSHFL']) <> '' ) echo '<tr><td class="col-md-4">Sheffield</td><td class="col-md-6">'.$rowB['IXSHFL'].'</td></tr>';
  	    echo '</table></div>
  	         </div>';
   	    if ( trim($rowB['IXPCWC']) > 0 || trim($rowB['IXACID']) <> '' )
        {
  	         echo '<div class="panel panel-default">
  	         <div class="panel-heading" data-toggle="collapse" data-target="#TEnvDet"><a><b>Environmental - Click to Toggle</b></a></div>
                <div id="TEnvDet" class="collapse"><table class="table table-striped table-condensed">';
            if ( trim($rowB['IXPCWC']) > 0 ) echo '<tr><td class="col-md-4">Post Consumer Waste</td><td class="col-md-6">'.$rowB['IXPCWC'].'</td></tr>';
            if ( trim($rowB['IXACID']) <> '' ) echo '<tr><td class="col-md-4">Acid Free</td><td class="col-md-6">'.$rowB['IXACID'].'</td></tr>';
  	         echo '</table></div>
       	        </div>';
        }
    }
}

/*************************************************
 Get Book Codes for an Item in the Price Catalog
 *************************************************/
function dspFilters($a_filt)
{
    global $db2conn;

    $rtree = substr($_SERVER['REQUEST_URI'], 1);
    $tree = explode('/', $rtree);
    if ($tree[1] == 'search')
    {
        $page = '/'.$tree[0].'/'.$tree[1].'/'.$tree[2].'/'.$tree[3].'/'.$tree[4];
    }
    else if ($tree[1] == 'products')
    {
        $page = '/'.$tree[0].'/'.$tree[1].'/'.$tree[2].'/'.$tree[3].'/'.$tree[4].'/'.$tree[5].'/'.$tree[6];
    }
    
    $fh = $_REQUEST['fh'];
    $fv = str_replace('_', '/', $_REQUEST['fv']);

    $ct = sizeof($a_filt);
    for ($x = 0; $x <=10; $x++) 
	{	
       if (!empty($a_filt[$x]))
       { 
	       foreach ($a_filt[$x] as $key => $value) 
	       {	
	           sort($value);
?>
<li><a class="btn btn-danger collapsed" data-toggle="collapse"
	data-target=".menu-<?php echo str_replace(' ', '-', $key);?>"><?php echo $key;?> <b
		class="caret"></b></a>
	<div
		class="nav-collapse menu-<?php echo str_replace(' ', '-', $key);?>"
		style="height: 0px; overflow: hidden;">
		<ul class="nav menu">
<?php 
             foreach ($value as $k => $f) 
	         {	
                $fval = str_replace('&', '$', $f);
	            if( $key == 'Manufacturer2' )
                {
    	           $query = "select PVVNNM from ". FILELIB . "/VENDR where PVVNNO = ?";
                    $stmt = db2_prepare($db2conn, $query);
                    if (! ($stmt)) {
                        db2_close($db2conn);
                        die("<b>ErrorVN " . db2_stmt_error() . ":" . db2_stmt_errormsg() . "</b>");
                    }
                    db2_bind_param($stmt, 1, "f", DB2_PARAM_IN);
                    db2_execute($stmt);
                    $row = db2_fetch_assoc($stmt);
                    if (!empty($fv))
                        echo '<li><a class="lnkFilter" href="'.$page.'/'.urlencode($fh).'!'.urlencode($key).'/'.urlencode(str_replace('/', '_', $fv)).'!'.urlencode(str_replace('/', '_', $fval)).'">'.$row['PVVNNM'].'</a></li>';
                    else
                        echo '<li><a class="lnkFilter" href="'.$page.'/'.urlencode($key).'/'.urlencode(str_replace('/', '_', $fval)).'">'.$row['PVVNNM'].'</a></li>';
                }
                else
                {
                    if (!empty($fv))
                        echo '<li><a class="lnkFilter" href="'.$page.'/'.urlencode($fh).'!'.urlencode($key).'/'.urlencode(str_replace('/', '_', $fv)).'!'.urlencode(str_replace('/', '_', $fval)).'">'.$f.'</a></li>';
                    else
                        echo '<li><a class="lnkFilter" href="'.$page.'/'.urlencode($key).'/'.urlencode(str_replace('/', '_', $fval)).'">'.$f.'</a></li>';
                }
	         }
         }
?>
        </ul>
	</div></li>
<?php 
       }
	}
}

/*************************************************
 Get Book Codes for an Item in the Price Catalog
 *************************************************/
function optState($sel)
{
    global $db2conn;

    echo 'HERE'.$sel;
    $query = "select * from WCPWEB/WEBSTATE order by StatDsc";
    $stmt = db2_prepare($db2conn, $query);
    if (! ($stmt)) 
    {
        db2_close($db2conn);
        die("<b>ErrorWS " . db2_stmt_error() . ":" . db2_stmt_errormsg() . "</b>");
    }
    db2_execute($stmt);
    echo '<option value="">Choose One</option>';
    while ( $row = db2_fetch_assoc($stmt) )
    {
        if ( $row['STATID'] == $sel )
            echo '<option value="'.$row['STATID'].'" selected>'.$row['STATDSC'].'</option>';
        else
            echo '<option value="'.$row['STATID'].'">'.$row['STATDSC'].'</option>';
    }

}

/*************************************************
 Authorize Credit Card
 *************************************************/
function AuthCC_sv()
{
    global $db2conn;

    $cctype = $_REQUEST['cctype'];
    $ccnum = $_REQUEST['ccnum'];
    $ccname = $_REQUEST['ccname'];
    $ccmm = $_REQUEST['ccmm'];
    $ccyy = $_REQUEST['ccyy'];
    
    if ( $ccyy < date('Y') )
        $Error = 'Credit Card Date has Expired - Year.';
    if ( $ccyy == date('Y') && $ccmm < date('m') )
        $Error = 'Credit Card Date has Expired - Month.';
    if ( $ccnum != '4111' )
        $Error = 'Credit Card Number is invalid.';
    if ( $cctype == 'W' )
        $Error = '';
    if ( !empty($Error))
    {
        return $Error;
    }
    else 
    {
        $acode = 'ABC123';
        return $acode;
    }
}

