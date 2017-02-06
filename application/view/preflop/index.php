<link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>

<div class="container">
	<h1>
		Shoving Ranges
	</h1>
	<div class="row">
		<div class="col-xs-3">
		<h2>Parameters</h2>
		<form>
		  <div class="form-group">
			<label>Total Players</label>
			<br>
			<?php foreach ($playerComps as $key => $pcomp): ?>
				<input <?= $pcomp->checked ?> type="radio" class="form-check-input"
				name="player_comp" value = "<?= $pcomp->value ?>" >
				<?= $pcomp->alias ?>
			<?php endforeach; ?>

		  <input value="<?= $params['players'] ?>" name="players" type="number" class="form-control" placeholder="Players left in Hand">

			</div>
		  <div class="form-group">

			<label>Action</label>
			<input value="shove" name="action" type="text" class="form-control" placeholder="Action">
		  </div>
		  <div class="form-group">

			<label>Effective Bet (in Big Blinds)</label>
			<br>
			<?php foreach ($betComps as $key => $bcomp): ?>
				<input <?= $bcomp->checked ?> type="radio" class="form-check-input"
				name="e_bet_comp" value = "<?= $bcomp->value ?>" >
				<?= $bcomp->alias ?>
			<?php endforeach; ?>
			<input value="<?= $params['e_bet'] ?>" name="e_bet" type="number" class="form-control" placeholder="Effective Bet">
		  </div>
		  <div class="form-group">
			<label for="exampleSelect1">Position</label>
			<?php
				$positions = array(
					'UTG','UTG+1','UTG+2','Dealer','Small Blind','Big Blind'
				);
			?>
			<?php foreach ($positions as $key => $position): ?>
				<div class="checkbox">
					<label>
						<?php if (isset($_GET['positions'])): ?>
							<?php if (in_array($position,$_GET['positions'])): ?>
								<input checked="" name="positions[]" type="checkbox" value="<?= $position ?>">
							<?php else: ?>
								<input name="positions[]" type="checkbox" value="<?= $position ?>">
							<?php endif; ?>
						<?php else: ?>
							<input name="positions[]" type="checkbox" value="<?= $position ?>">
						<?php endif; ?>

						<?= $position ?>
					</label>
				</div>
			<?php endforeach; ?>
			<select name="position" class="form-control" id="exampleSelect1">
			  <?php foreach($positions as $position): ?>
					<?php if ($position == $params['position']): ?>
						<option selected="" value="<?= $position?>">
							<?= $position?>
						</option>
					<?php else: ?>
						<option value="<?= $position?>">
							<?= $position?>
						</option>
					<?php endif; ?>
			  <?php endforeach;?>
			</select>
		  </div>
		  <fieldset class="form-group">
				<?php foreach ($resultRadio as $key => $rRadio): ?>
					<div class="form-check">
					  <label class="form-check-label">
						<input <?= $rRadio->checked ?> type="radio" class="form-check-input"
						name="is_me" value="<?= $rRadio->value ?>">
							<?= $rRadio->alias ?>
					  </label>
					</div>
				<?php endforeach; ?>
		  </fieldset>
		  <button type="submit" class="btn btn-primary">Submit</button>
		</form>
		</div>
		<div class="col-xs-9">
			<h2>Results:</h2>
			<?php foreach ($stats as $key => $stat): ?>
				<b><?= $key ?>:</b>
				<?= number_format($stat,2) ?>%
				<br>
			<?php endforeach; ?>
			<br>
			<div class="table-responsive">
				<table class="table data-table table-striped table-bordered">
					<thead>
						<tr>
							<th>Hand Number</th>
							<th>Hand</th>
							<th>Percentage</th>
							<th>Action</th>
							<th>Effective Bet</th>
							<th>Position</th>
							<th>Exclude</th>
						</tr>
					</thead>
					<?php foreach ($actions as $key => $action): ?>
						<tr>
							<td><?= $action->hand_id ?></td>
							<td><?= $action->hand ?></td>
							<td><?= $action->percentage ?>%</td>
							<td><?= $action->action ?></td>
							<td><?= $action->effective_bet_bbs ?></td>
							<td><?= $action->position ?></td>
							<td>
								<input name="excludes[]" type="checkbox" value="<?= $action->hand_id ?>">
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			</div>
		</div>
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
