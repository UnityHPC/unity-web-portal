<?php
require "../../resources/autoload.php";

require_once config::PATHS["templates"] . "/header.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form was submitted
    $subject = $_POST["subject"];
    $prio = $_POST["prio"];
    $type = $_POST["type"];

    if (empty($subject) || empty($prio) || empty($type)) {
        die();
    }

    $mail_data = array(
        "mail" => $user->getMail(),
        "name" => $user->getName(),
        "subject" => $subject,
        "message" => ""
    );

    switch ($type) {
        case "soft":
            $soft_link = $_POST["soft-link"];
            $soft_details = $_POST["soft-details"];

            if (empty($soft_link)) {
                die();
            }

            $mail_data["message"] = "<p>Software Request</p><p><b>Link to Software: </b><a href='$soft_link'>$soft_link</a></p><p>Description of Software (optional): $soft_details</p>";

            break;
        case "bug":
            $bug_issue = $_POST["bug-issue"];
            $bug_steps = $_POST["bug-reproduce"];

            if (empty($bug_issue) || empty($bug_steps)) {
                die();
            }

            break;
        case "feat":
            $feat_message = $_POST["feat-message"];

            if (empty($feat_message)) {
                die();
            }

            break;
        case "gen":
            $gen_message = $_POST["gen-message"];

            if (empty($gen_message)) {
                die();
            }



            break;
    }
}
?>

<h1>Request Support</h1>

<p>Before submitting a ticket, please reference our documentation and FAQ page for a potential answer.</p>

<form id="contact-form" action="" method="POST" onSubmit="return confirm('<?php echo unity_locale::CONTACT_WARN_SEND; ?>');">
    <input type="text" name="subject" placeholder="<?php echo unity_locale::LABEL_SUBJECT; ?>">

    <select required="true" name="prio" form="contact-form">
        <option disabled selected value>-- Select Priority --</option>
        <option value="1">Critical</option>
        <option value="2">High</option>
        <option value="3">General</option>
    </select>

    <select required="true" name="type" form="contact-form">
        <option disabled selected value>-- Select Support Type --</option>
        <option value="soft">Software Request</option>
        <option value="bug">Bug Report</option>
        <option value="feat">Feature Request</option>
        <option value="gen">General Inquiry</option>
    </select>

    <input type="submit" value="Submit Ticket">
</form>

<script>
    // Define Form Sections
    var cfGeneral = `
    <div class="formSection" id="cfGeneral">
        <textarea required="true" placeholder="<?php echo unity_locale::LABEL_MESSAGE; ?>" name="gen-message" form="contact-form"></textarea>
    </div>
    `;
    var cfSoftware = `
    <div class="formSection" id="cfSoftware">
        <input required="true" type="text" name="soft-link" placeholder="Link to requested software">
        <textarea required="false" placeholder="Additional details about the requested software, such as required build parameters (optional)" name="soft-details" form="contact-form"></textarea>
    </div>
    `;
    var cfBug = `
    <div class="formSection" id="cfBug">
        <textarea required="true" placeholder="Describe the issue you are encountering" name="bug-issue" form="contact-form"></textarea>
        <textarea required="true" placeholder="Describe the steps to reproduce this bug report" name="bug-reproduce" form="contact-form"></textarea>
    </div>
    `;
    var cfFeature = `
    <div class="formSection" id="cfFeature">
        <textarea required="true" placeholder="Describe the feature request and a possible implementation" name="feat-message" form="contact-form"></textarea>
    </div>
    `;
    var afterSelector = "#contact-form > select[name=type]";

    function resetForm() {
        $("#contact-form > div.formSection").remove();
        $("#contact-form > input[type=submit]").hide();
    }

    $("#contact-form select[name=type]").change(function() {
        resetForm();

        switch ($(this).val()) {
            case "soft":
                $(afterSelector).after(cfSoftware);
                break;
            case "bug":
                $(afterSelector).after(cfBug);
                break;
            case "feat":
                $(afterSelector).after(cfFeature);
                break;
            case "gen":
                $(afterSelector).after(cfGeneral);
                break;
        }

        $("#contact-form > input[type=submit]").show();
    });
</script>

<?php
printMessages($errors, unity_locale::CONTACT_MES_SENT);

require_once config::PATHS["templates"] . "/footer.php";
?>