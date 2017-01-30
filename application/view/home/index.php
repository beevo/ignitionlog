<link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>

<div class="container">
  <h1>Home!</h1>
  <p>Welcome to the new PHP website. Please click around the site and let me know what you think.</p>
  <p>This is the MVC version of the site.</p>
  <div class="table-responsive">
    <table class="table data-table table-striped table-bordered">
      <thead>
        <tr>
          <th>Tournament Id</th>
          <th>Name</th>
          <th>Date</th>
          <th>Type</th>
          <th>Game</th>
          <th>Rank</th>
          <th>Buy In</th>
          <th>Cashout</th>
          <th>Profit</th>
        </tr>
      </thead>
      <?php foreach ($histories as $key => $history): ?>
        <tr>
          <td><?= $history->tournament_id ?></td>
          <td><?= $history->name ?></td>
          <td><?= $history->date ?></td>
          <td><?= $history->type ?></td>
          <td><?= $history->game ?></td>
          <td><?= $history->rank ?></td>
          <td><?= number_format($history->buyin,2) ?></td>
          <td><?= number_format($history->cashout,2) ?></td>
          <td><?= number_format($history->cashout - $history->buyin,2)?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<script type="text/javascript">
  jQuery(document).ready(function(){
    $(document).ready(function() {
        $('table').DataTable();
    } );
  });
</script>
