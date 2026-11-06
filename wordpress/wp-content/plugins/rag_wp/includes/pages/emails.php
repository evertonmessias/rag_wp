<!--Arquivo: wp-content/plugins/novoicode/includes/pages/emails.php -->
<div class="about-novoicode">
<br>
    <h2 class="title">Registro de E-Mails</h2>
    <table id="temails" class="table table-striped table-bordered display" style="width:100%">
        <thead>
            <tr>
                <th>Id</th>
                <th>De</th>                
                <th>Para</th>
                <th>Assunto</th>
                <th>Data</th>
                <th>Codigo</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            global $wpdb;
            $table_name = $wpdb->prefix . 'emails';
            $sql = "SELECT * FROM $table_name ORDER BY id DESC LIMIT 500;";            
            $results = $wpdb->get_results($sql);
            foreach ($results as $item) {
                $data = explode("-", explode(" ", $item->data)[0]);
                $datahora = $data[2] . "/" . $data[1] . "/" . $data[0] . " , " . explode(" ", $item->data)[1];
                echo "<tr><td>" . $item->id . "</td><td>" . $item->de . "</td><td>" . $item->para . "</td><td>" . $item->assunto . "</td><td>" . $datahora . "</td><td>" . $item->codigo . "</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>