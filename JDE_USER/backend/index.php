<?php
/**
 * index.php — Backend controller
 * Handles session start and contact form processing, then renders the homepage view.
 */
session_start();

require '../html/index.view.php';