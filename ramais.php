<?php
    
    $conn = mysql_connect("10.10.10.76","fdazzi","frdazzi1980") or 
            die("Não foi possível conectar ao banco do Asterisk");
    mysql_select_db("asterisk") or 
            die("Erro seleção de banco");
    
    $query   = "select * from users where extension <> '' order by name";
    $result  = mysql_query($query);
    
    echo "<h1>Ramais da SUPRISERVICE</h1>";
    echo "<table width='800' border='1'>";
    echo "<tr>";
    echo "<th width='300'>Nome</th>";
    echo "<th width='100' align='center'>Ramal</th>";
    echo "<th width='300' align='center'>E-mail</th>";
    echo "<th width='100' align='center'>Celular</th>";
    echo "</tr>";
    while ($valores = mysql_fetch_assoc($result)){
        echo "<tr>";
        echo "<td>".$valores['name']."</td>";
        echo "<td align='center'>{$valores['extension']}</td>";
        echo "<td>&nbsp;</td>";
        echo "<td>&nbsp;</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysql_close($conn);
?>
