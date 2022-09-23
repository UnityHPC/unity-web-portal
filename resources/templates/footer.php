</main>

<footer>
  <div id="footerLogos">
    <?php

    $footer_logos = $BRANDING["footer"]["logos"];
    $footer_links = $BRANDING["footer"]["links"];
    $footer_titles = $BRANDING["footer"]["title"];
    for ($i = 0; $i < count($footer_logos); $i++) {
        echo
        "<a href='" . $footer_links[$i] . "'>
        <img src='" . $CONFIG["site"]["prefix"] . "/res/" . $footer_logos[$i] . "' 
        draggable='false' title='" . $footer_titles[$i] . "'></a>";
    }
    ?>

  </div>
  <div class="footerBlock">
    <span><?php echo $BRANDING["footer"]["text"] ?></span>&nbsp;&nbsp;
    <a href="<?php echo $CONFIG["site"]["prefix"]; ?>/priv.php">Site Policy</a>
  </div>
</footer>

</body>

<script src="<?php echo $CONFIG["site"]["prefix"]; ?>/js/global.js"></script>
<script src="<?php echo $CONFIG["site"]["prefix"]; ?>/js/tables.js"></script>

</html>
