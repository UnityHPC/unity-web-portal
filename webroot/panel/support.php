<?php
require "../../resources/autoload.php";

require_once config::PATHS["templates"] . "/header.php";
?>

<h1>Support</h1>
<hr>

<section id="faq">

<p><strong>How do I connect to, and start using the cluster?</strong></p>
<p>Refer to connection instructions on our documentation page <a href="https://unity.rc.umass.edu/docs/#connecting/ssh/">here</a>. You can connect over SSH or JupyterLab.</p>

<p><strong>When I connect over SSH I get a message saying "permission denied (public key)"</strong></p>
<p>This can be due to a few reasons:</p>
<ul>
    <li>You have not provided your public key while connecting. Using <pre>ssh -i [private_key_location] [user]@unity.rc.umass.edu should help.</li>
    <li>You are not assigned to at least 1 PI group. We require at least 1 PI to endorse your account before you can use the cluster. Request to join a PI on the <a href="<?php echo config::PREFIX; ?>/panel/groups.php">My PIs</a> page.</li>
    <li>You have not added a public key to your account on Unity yet. You can do this on the <a href="<?php echo config::PREFIX; ?>/panel/account.php">Account Settings</a> page.</li>
</ul>

<p><strong>Where can I find software to use on the cluster?</strong></p>
<p>Most of our software is package installed and is available by default. Many other software which have different versions are available as modules. The command <pre>module av</pre> will print all available modules. Then you can use <pre>module load [name]</pre> to load a module and have access to its binaries.</p>

<p><strong>How much storage do I get on Unity and is it backed up?</strong></p>
<p>Your home directory <pre>/home/[user]</pre> has 500GB of storage which can be expanded on request. Your scratch space <pre>/scratch/[user]</pre> has unlimited storage, but inactive files beyond 90 days will be auto-deleted without warning.</p>
<p>We do not provide backup solutions by default. We take a snapshot of all storage at 1 AM every day for the past 48 hours. This way, if you accidentally deleted something it wouldn't be difficult to get it back within that time frame.</p>

<h2>Any more questions, bug reports, software requests, etc.? Email us at <a href="mailto:hpc@umass.edu">hpc@umass.edu</a>.</h2>

</section>

<?php
printMessages($errors, unity_locale::CONTACT_MES_SENT);

require_once config::PATHS["templates"] . "/footer.php";
?>