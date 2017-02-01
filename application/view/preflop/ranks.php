<link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>

<div class="container">
  <h1>Home!</h1>
  <p>Welcome to the new PHP website. Please click around the site and let me know what you think.</p>
  <p>This is the MVC version of the site.</p>
  <div>
  <table cellpadding="3" cellspacing="0" border="0" style="width: 67%; margin: 0 auto 2em auto;">
        <tbody>
            <tr id="filter_col1" data-column="0">
                 <td align="center">
					<input placeholder="Hand" type="text" class="column_filter" id="col0_filter">
				</td>
		    </tr>
          <tr id="filter_col1" data-column="1">
                 <td align="center">
					<input placeholder="Ranking" type="text" class="column_filter" id="col1_filter">
				</td>
		    </tr>
        </tbody>
    </table>
  </div>
  <div class="table-responsive">
    <table class="table data-table table-striped table-bordered">
      <thead>
        <tr>
          <th>Hand</th>
          <th>Ranking</th>
          <th>Percentage</th>
          <th>Chart</th>
        </tr>
      </thead>
      <?php foreach ($ranks as $key => $rank): ?>
        <tr>
          <td><?= $rank->hand ?></td>
          <td><?= $rank->ranking ?></td>
          <td><?= $rank->percentage ?>%</td>
          <td><?= $rank->chart ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<script type="text/javascript">
	function filterGlobal () {
		$('.data-table').DataTable().search(
			$('#global_filter').val(),
			$('#global_regex').prop('checked'),
			$('#global_smart').prop('checked')
		).draw();
	}
	 
	function filterColumn ( i ) {
		$('.data-table').DataTable().column( i ).search(
			$('#col'+i+'_filter').val(),
			$('#col'+i+'_regex').prop('checked'),
			$('#col'+i+'_smart').prop('checked')
		).draw();
	}
  jQuery(document).ready(function(){
    $(document).ready(function() {
        $('.data-table').DataTable();
		  $('input.global_filter').on( 'keyup click', function () {
        filterGlobal();
    } );
 
    $('input.column_filter').on( 'keyup click', function () {
        filterColumn( $(this).parents('tr').attr('data-column') );
    } );
    } );
  });
</script>
