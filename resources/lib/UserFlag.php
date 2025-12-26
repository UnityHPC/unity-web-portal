<?php

namespace UnityWebPortal\lib;

enum UserFlag: string
{
    case ADMIN = "admin";
    case GHOST = "ghost";
    case IDLELOCKED = "idlelocked";
    case LOCKED = "locked";
    case QUALIFIED = "qualified";
}
