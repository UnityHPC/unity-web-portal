</main>

<footer>
  <div id="footerLogos">
    <img draggable="false" title="University of Massachusetts Amherst" src="/res/umass.png">
    <img draggable="false" title="Massachusetts Green High Performance Computing Center" src="/res/mghpcc.png">
  </div>
  <div class="footerBlock">
    <span>Copyright &copy;<?php echo date("Y") . " " . config::CLUSTER["org"]; ?></span>&nbsp;&nbsp;<a href="/priv.php">Site Policy</a> - <a target="_blank" href="<?php echo config::APP["repo"]; ?>"><?php echo config::APP["version"]; ?></a>
  </div>
</footer>

</body>

<script src="<?php echo config::PREFIX; ?>/js/global.js"></script>
<script src="<?php echo config::PREFIX; ?>/js/tables.js"></script>

</html>
