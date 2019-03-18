<?php
require_once("includes.php");
require_once("header.php");

$order = isset($_GET['order']) ? $_GET['order'] : null;
$i = 0; //increment du nombre de clients // couleur du tabeau (oldstyle)
$TotalJobBytes = 0;
$TotalVolFactu = 0;
$nbr_depassements = 0;
$TotalDepassement = 0;
$ordering = 'customer_id ASC';

if (in_array($order, array('Depassement', 'Bytes', 'vol_factu'))) {
    $ordering = $order . ' DESC';
}

$query = '
    SELECT billing.customer_name, SUM(Job.JobBytes) AS Bytes, billing.vol_factu, billing.customer_id, sum(Job.JobBytes)-billing.vol_factu AS Depassement
    FROM Job
    INNER JOIN client_customer_assoc grp ON grp.id_client = Job.ClientId
    INNER JOIN customer_billing billing ON billing.customer_id=grp.customer_id
    WHERE full_billing = "false"
    AND Job.Type = "B"
    GROUP BY billing.customer_name
    ORDER BY ' . $ordering;

// Requete pour le nouveau mode de facturation
$query_full_billing = '
    SELECT customer_name, SUM(MaxFull) AS Bytes, vol_factu, customer_id, SUM(MaxFull)-vol_factu AS Depassement
    FROM (
        SELECT billing.customer_name, MAX(Job.JobBytes) AS MaxFull, billing.vol_factu, billing.customer_id
        FROM Job
        INNER JOIN client_customer_assoc grp ON grp.id_client = Job.ClientId
        INNER JOIN customer_billing billing ON billing.customer_id=grp.customer_id
        WHERE full_billing = "true" AND Level = "F"
        AND Job.Type = "B"
        GROUP BY grp.name
    ) AS Full_Max
    GROUP BY customer_name
    ORDER BY ' . $ordering;
?>

<body>
<?php include('navbar.php'); ?>

<h1>Backup Reporting</h1>
<table class="table table-striped table-bordered table-hover table-condensed">
    <thead>
    <tr class="info">
        <th><a href="report.php">Name</a></th>
        <th><a href="report.php?order=Bytes">Total</a></th>
        <th><a href="report.php?order=vol_factu">Factur&eacute;</a></th>
        <th><a href="report.php?order=Depassement">Depassement</a></th>
    </tr>
    </thead>

    <tbody>
    <?php
    foreach ($bdd->query($query) as $row) : $i++;
        $TotalJobBytes += $row['Bytes'];
        $TotalVolFactu += $row['vol_factu'];
        ?>
        <tr>
            <td>
                <a href="details.php?clientId=<?php echo $row['customer_id']; ?>"><?php echo $row['customer_name']; ?></a>
            </td>
            <td><?php echo FileSizeConvert($row['Bytes']); ?></td>
            <td><?php echo FileSizeConvert($row['vol_factu']); ?></td>
            <td>
                <?php
                if ($row['Depassement'] > 0) :
                    $DepasPercent = round(($row['Depassement'] / $row['vol_factu']) * 100, 2);
                    $HDepassement = FileSizeConvert($row['Depassement']);
                    ?>
                    <?php echo $HDepassement; ?> <img
                        src="res/fouet.gif"/> soit <?php echo $DepasPercent; ?>&#37; de d&eacute;passement
                    <?php
                    $nbr_depassements++;
                    $TotalDepassement += $row['Depassement'];
                endif;
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <tr class="info">
        <th colspan=4><a name="New">Max FULL JobBytes</a></th>
    </tr>
    <?php
    foreach ($bdd->query($query_full_billing) as $row) : $i++;
        $TotalJobBytes += $row['Bytes'] * 7;
        $TotalVolFactu += $row['vol_factu'] * 7;
        ?>
        <tr>
            <td>
                <a href="details.php?clientId=<?php echo $row['customer_id']; ?>"><?php echo $row['customer_name']; ?></a>
            </td>
            <td><?php echo FileSizeConvert($row['Bytes']); ?></td>
            <td><?php echo FileSizeConvert($row['vol_factu']); ?></td>
            <td>
                <?php
                if ($row['Depassement'] > 0) :
                    $DepasPercent = round(($row['Depassement'] / $row['vol_factu']) * 100, 2);
                    $HDepassement = FileSizeConvert($row['Depassement']);
                    ?>
                    <?php echo $HDepassement; ?>
                    <img src="res/fouet.gif"/> soit <?php echo $DepasPercent; ?>&#37; de d&eacute;passement
                    <?php
                    $nbr_depassements++;
                    $TotalDepassement += $row['Depassement'] * 7;
                endif;
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr class="danger">
        <td>TOTAL</td>
        <td><?php echo FileSizeConvert($TotalJobBytes); ?></td>
        <td><?php echo FileSizeConvert($TotalVolFactu); ?></td>
        <td><?php echo FileSizeConvert($TotalDepassement); ?></td>
    </tr>
    </tfoot>
</table>

<p>Les totaux du nouveau mode de factu sont x7 pour approximer la r&eacute;alit&eacute;.</p>
<address>
    <div>Nombre de clients : <?php echo $i; ?></div>
    <div>Nombre de d&eacute;passements : <?php echo $nbr_depassements; ?>... t'as encore du taff !!!</div>
</address>

</body>
</html>
