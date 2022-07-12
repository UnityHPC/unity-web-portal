<?php

class branding {
    public const URL = "https://unity.rc.umass.edu/";
    public const DOCS_URL = "https://unity.rc.umass.edu/docs";
    public const LANG = "en";

    public const COLORS = array(
        "light_background" => "#ffffff",  // Background color when in light mode
        "light_foreground" => "#1a1a1a",  // Text color when in light mode
        "light_foreground_smallheader" => "#666666",  // Disabled text when in light mode
        "light_borders" => "#dddddd",  // Color used for borders and dividers in light mode
        "light_footer_background" => "#fafafa",  // Background color for footer in light mode
        "light_footer_foreground" => "#bbbbbb",  // Text color for footer in light mode
        "light_panel_background" => "#e6e6e6",  // Background color for panels on the page (ie. cluster notices)
        "accent" => "#881c1c",  // Primary accent color
        "accent_hover" => "#9c2020",  // On hover color for element using accent color
        "accent_active" => "#a92323",  // Active element color for element using accent color
        "accent_disabled" => "#e48181",  // Disabled element color for element using accent color
        "accent_foreground" => "#ffffff"  // Text color when accent color is background
    );

    public const SUPPORT = array(
        "email" => "hpc@umass.edu"
    );

    public const FOOTER = array(
        "text" => "Unity Web Portal Version 0.7-BETA",
        "logos" => array(
            "umass.png",
            "uri.png",
            "mghpcc.png"
        )
    );
}