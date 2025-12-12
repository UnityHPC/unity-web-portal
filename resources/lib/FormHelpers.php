<?php

namespace UnityWebPortal\lib;

function getCSRFField(): string
{
    return CSRFToken::getHiddenInput();
}

function getCSRFToken(): string
{
    return CSRFToken::getToken();
}
