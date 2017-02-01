
<div class="container">
<h1>
Shoving Ranges of Postions of x Players
</h1>
	<div class="row">
		<div class="col-xs-6">
		<h2>Parameters</h2>
		<form>
		  <div class="form-group">
			<label>Player Left to Act</label>
			<br>			
			<input checked="" type="radio" class="form-check-input" name="player_comp" value = "eq" >
			Equal To:
			<input type="radio" class="form-check-input" name="player_comp" value = "lt" >
			Less Than:
			<input type="radio" class="form-check-input" name="player_comp" value = "gt" >
			Greater Than:
		  <input value="6" name="players" type="number" class="form-control" placeholder="Players left in Hand">

			</div>
		  <div class="form-group">
		  
			<label>Action</label>
			<input disabled value="shove" name="action" type="text" class="form-control" placeholder="Action">
		  </div>
		  <div class="form-group">
		  
			<label>Effective Bet (in Big Blinds)</label>
			<br>
			<input checked="" type="radio" class="form-check-input" name="e_bet_comp" value = "eq" >
			Equal To:
			<input type="radio" class="form-check-input" name="e_bet_comp" value = "lt" >
			Less Than:
			<input type="radio" class="form-check-input" name="e_bet_comp" value = "gt" >
			Greater Than:
			<input value="10" name="e_bet" type="number" class="form-control" placeholder="Action">
		  </div>
		  <div class="form-group">
			<label for="exampleSelect1">Position</label>
			<?php
				$positions = array(
					'UTG','UTG+1','UTG+2','Dealer','Small Blind','Big Blind'
				);
			?>
			<select class="form-control" id="exampleSelect1">
			  <?php foreach($positions as $position): ?>
				<option value="<?= $position?>"><?= $position?></option>	
			  <?php endforeach;?>
			</select>
		  </div>
		  <!--
		  <div class="form-group">
			<label for="exampleInputPassword1">Password</label>
			<input type="password" class="form-control" id="exampleInputPassword1" placeholder="Password">
		  </div>
		  <div class="form-group">
			<label for="exampleSelect1">Example select</label>
			<select class="form-control" id="exampleSelect1">
			  <option>1</option>
			  <option>2</option>
			  <option>3</option>
			  <option>4</option>
			  <option>5</option>
			</select>
		  </div>
		  <div class="form-group">
			<label for="exampleSelect2">Example multiple select</label>
			<select multiple class="form-control" id="exampleSelect2">
			  <option>1</option>
			  <option>2</option>
			  <option>3</option>
			  <option>4</option>
			  <option>5</option>
			</select>
		  </div>
		  <div class="form-group">
			<label for="exampleTextarea">Example textarea</label>
			<textarea class="form-control" id="exampleTextarea" rows="3"></textarea>
		  </div>
		  <div class="form-group">
			<label for="exampleInputFile">File input</label>
			<input type="file" class="form-control-file" id="exampleInputFile" aria-describedby="fileHelp">
			<small id="fileHelp" class="form-text text-muted">This is some placeholder block-level help text for the above input. It's a bit lighter and easily wraps to a new line.</small>
		  </div>
		  !-->
		  <fieldset class="form-group">
			<div class="form-check">
			  <label class="form-check-label">
				<input type="radio" class="form-check-input" name="is_me" value="-1" checked>
				Include All Results
			  </label>
			</div>
			<div class="form-check">
			<label class="form-check-label">
				<input type="radio" class="form-check-input" name="is_me" value="1" >
				Include Only My Results
			  </label>
			</div>
			<div class="form-check">
			<label class="form-check-label">
				<input type="radio" class="form-check-input" name="is_me" value = "0" >
				Exclude My Results
			  </label>
			</div>
		  </fieldset>
		  <button type="submit" class="btn btn-primary">Submit</button>
		</form>
		</div>
		<div class="col-xs-6">
			<h2>Results</h2>
		</div>
	</div>
</div>