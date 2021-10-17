<?php
require "../resources/autoload.php";

require_once config::PATHS["templates"] . "/header.php";
?>

<h1><?php echo unity_locale::PRIV_HEADER_AUP; ?></h1>
<label>12/14/2020</label>

<p>By using resources associated with Unity, you agree to comply with the following conditions of use.  This is an extension of the University of Massachussetts Amherst Information Technology Acceptable Use Policy, which can be found <a target="_blank" href="https://www.umass.edu/it/security/acceptable-use-policy">here</a>.</p>

<ol>
    <li>You will not use Unity resources for illicit financial gain, such as virtual currency mining, or any unlawful purpose, nor attempt to breach or circumvent any Unity administrative or security controls. You will comply with all applicable laws, working with your home institution and the specific Unity service providers utilized to determine what constraints may be placed on you by any relevant regulations such as export control law or HIPAA.</li>
    <li>You will respect intellectual property rights and observe confidentiality agreements.</li>
    <li>You will protect the access credentials (e.g., passwords, private keys, and/or tokens) issued to you or generated to access Unity resources; these are issued to you for your sole use.</li>
    <li>You will immediately report any known or suspected security breach or loss or misuse of Unity access credentials to <a href="mailto:hpc@it.umass.edu">hpc@it.umass.edu</a>.</li>
    <li>You will have only one Unity User account and will keep your profile information up-to-date.</li>
    <li>Use of resources and services through Unity is at your own risk. There are no guarantees that resources and services will be available, that they will suit every purpose, or that data will never be lost or corrupted. Users are responsible for backing up critical data.</li>
    <li>Logged information, including information provided by you for registration purposes, is used for administrative, operational, accounting, monitoring and security purposes. This information may be disclosed, via secured mechanisms, only for the same purposes and only as far as necessary to other organizations cooperating with Unity .</li>
</ol>

<p>The Unity team reserves the right to restrict access to any individual/group found to be in breach of the above.</p>

<?php
require_once config::PATHS["templates"] . "/footer.php";
?>
