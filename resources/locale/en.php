<?php

/**
 * English (EN) Locale Definitions for Unity Website
 * All static vars/methods should be in the format <page>_<type>_<description>
 * type = err,warn,mes,label,header
 */
class unity_locale {
    //
    // General
    //
    public const ERR = "An error occured, please contact the admins.";
    public const LABEL_NAME = "Name";
    public const LABEL_USERID = "User ID";
    public const LABEL_ACTIONS = "Actions";
    public const LABEL_MAIL = "Email";
    public const LABEL_SUBJECT = "Subject";
    public const LABEL_MESSAGE = "Message";
    public const LABEL_APPLY = "Apply Changes";
    public const MES_APPLY = "Changes Applied";

    public const LABEL_ERROR = "Error:";
    public const LABEL_SUCCESS = "Success:";

    //
    // Home (/index.php)
    //
    public const HOME_HEADER_MAIN = "Unity Cluster";

    //
    // Authentication (/panel-auth.php)
    //
    public const AUTH_HEADER_MAIN = "Campus Authentication";

    //
    // AUP/Priv (/priv.php)
    //
    public const PRIV_HEADER_PRIVACY = "Privacy Policy";
    public const PRIV_HEADER_AUP = "Acceptable User Policy";

    //
    // About (/about.php)
    //
    public const ABOUT_HEADER_MAIN = "About Us";

    //
    // Contact Us (/contact.php)
    //
    public const CONTACT_HEADER_MAIN = "Contact Us";
    public const CONTACT_ERR_NAME = "Enter a name";
    public const CONTACT_ERR_MAIL = "Enter a valid email";
    public const CONTACT_ERR_SUBJECT = "Enter a subject";
    public const CONTACT_ERR_MESSAGE = "Enter a message";
    public const CONTACT_LABEL_SEND = "Send Message";
    public const CONTACT_MES_SENT = "Message sent";
    public const CONTACT_WARN_SEND = "Are you sure you would like to send this message?";

    //
    // Cluster Status (/cluster-status.php)
    //
    public const CLUSTER_HEADER_MAIN = "Cluster Status";
    public const CLUSTER_LABEL_USAGE = "Total CPU Usage";
    public const CLUSTER_LABEL_UP = "UP";
    public const CLUSTER_LABEL_DOWN = "DOWN";

    //
    // Panel Home (/panel/index.php)
    //
    public const PANEL_HEADER_MAIN = "User Panel";

    //
    // New Account (/panel/new_account.php)
    //
    public const NEWACC_HEADER_MAIN = "Request Account";
    public const NEWACC_LABEL_SELECTPI = " -- Select Principal Investigator -- ";
    public const NEWACC_LABEL_EXISTINGPI = " -- Existing PI --";
    public const NEWACC_ERR_PI = "Select a valid PI, or select \"New PI\"";
    public const NEWACC_LABEL_ARCHIVAL = "You are an <b>archived</b> account. This could be due to you closing your account, or your account being removed. Please select a PI to request to restore your account. Your files are archived and will be restored upon approval.";
    public const NEWACC_LABEL_AWAITING = "Your account request has been submitted but it has not yet been approved. You will not be able to submit this form.";
    public const NEWACC_LABEL_REQUEST = "Request Account";
    public const NEWACC_MES_SUCCESS = "Your account request has been submitted. You will receive email confirmation once your account is created.";

    //
    // Account Settings (/panel/account.php)
    //
    public const ACCOUNT_HEADER_MAIN = "Account Settings";
    public const ACCOUNT_LABEL_NEW = "Add New Key";
    public const ACCOUNT_LABEL_GENERATE = "Generate Key Pair";
    public const ACCOUNT_LABEL_GENWIN = "Windows (PuTTY)";
    public const ACCOUNT_LABEL_GENLIN = "Linux / Max (OpenSSH)";
    public const ACCOUNT_LABEL_REMKEY = "Remove SSH Key";
    public const ACCOUNT_LABEL_KEY = "ssh-rsa AAAAB3Nza...";

    //
    // My Groups (/panel/groups.php)
    //
    public const GROUP_HEADER_MAIN = "My Groups";
    public const GROUP_BTN_JOIN_PI = "Join PI";
    public const GROUP_BTN_ACTIVATE_PI = "Activate PI Account";
    public const GROUP_WARN_ACTIVATE_PI = "Are you sure you want to activate your PI account? You need to be a PI at your institution for this request.";
    
    public static function GROUP_WARN_REMOVE($name) {
        return "Are you sure you would like to leave the PI group $name?";
    }

    //
    // Mass Email (/admin/mass_email.php)
    //
    public const MASS_HEADER_MAIN = "Mass Email";
    public const MASS_ERR_SUBJECT = "Enter a valid subject";
    public const MASS_ERR_MESSAGE = "Enter a valid message";
    public const MASS_WARN_SEND = "Are you sure you would like to send this mass email?";
    public const MASS_LABEL_SEND = "Send Mass Email";
    public const MASS_MES_SEND = "Mass email was sent successfully";

    //
    // User Management (/admin/user-mgmt.php)
    //
    public const USER_HEADER_MAIN = "User Management";

    //
    // Mail Templates
    //
    public static function MAIL_LABEL_FOOTER($url) {
        return "You are receiving this email because you have an account on the <a target='_blank' href='$url'>Unity Cluster</a> at UMass Amherst. If you would like to stop receiving these emails, you may request to close your account by replying to this email.";
    }

    public const MAIL_HEADER_PIREQUEST = "New User Request";
    public const MAIL_LABEL_PIREQUEST = "A user has requested an account in your PI group:";

    public const MAIL_HEADER_ADREQUEST = "New PI Request";
    public const MAIL_LABEL_ADREQUEST = "A user has requested a new PI account:";

    public const MAIL_HEADER_PIJOIN = "PI Request Approved";
    public static function MAIL_LABEL_PIJOIN($group) {
        return "Your request to join the PI group $group has been approved. You may view this group in in the 'My Groups' page on the user portal";
    }

    public const MAIL_HEADER_PIDENY = "PI Request Denied";
    public static function MAIL_LABEL_PIDENY($group) {
        return "Your request to join the PI group $group has been denied. You may follow up with the group owner if necessary.";
    }

    public const MAIL_HEADER_PIREM = "Removed from Group";
    public static function MAIL_LABEL_PIREM($group) {
        return "You have been removed from the PI group $group. Your files have not been removed, only your association with $group. Feel free to reply with any questions.";
    }

    public const MAIL_HEADER_ADMIN_APP_PI = "PI Account Approved";
    public const MAIL_LABEL_ADMIN_APP_PI = "Your request to create a PI account has been approved. You can manage your group on the 'PI Management' page in the user portal.";

    public const MAIL_HEADER_ADMIN_DENY_PI = "PI Account Denied";
    public const MAIL_LABEL_ADMIN_DENY_PI = "Your request to create a PI account has been denied. This could be for a number of reasons. Most comonly, you may not be a principal investigator at your institution. Feel free to reply to this email with any questions.";

    public const MAIL_HEADER_ADMIN_DISBAND_PI = "PI Account Disbanded";
    public const MAIL_LABEL_ADMIN_DISBAND_PI = "Your PI group has been disbanded. This means you and all users part of the group no longer have an association with it. Your user accounts and files are still intact, as they are seperate from PI accounts";

    public const MAIL_HEADER_LEFT_PI = "User Left PI Group";
    public const MAIL_LABEL_LEFT_PI = "A user has left your PI group, details below:";

    public static function MAIL_MES_ACTIVATE($url) {
        return "You can approve this account <a href='$url'>here</a>.";
    }
}