<?php

/**
 * Encore a base64 string without the weird chars at the end.
 *
 * @param $data
 *  String to encode.
 * @return string
 *  Encoded string.
 */
function base64url_encode($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Decode a base64 string without the weird chars at the end.
 *
 * @param $data
 *  String to decode.
 * @return string
 *  Decoded string.
 */
function base64url_decode($data) {
  return base64_decode(
    str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)
  );
}

/**
 * Return a "themed" HTML string that represent the file list in a directory.
 *
 * @param null $dir
 * @return null|string
 */
function generate_file_list($dir = NULL) {
  if (empty($dir)) {
    return NULL;
  }
  $output = '';
  $scanned_directory = scan_directory($dir);
  foreach ($scanned_directory as $key => $file) {
    $file_encoded = base64url_encode($file);
    $classes = '';
    if (isset($_GET['mail']) && $_GET['mail'] == $file_encoded) {
      $classes = 'active';
    }
    $output .= '<li class="' . $classes . '"><a href="/?mail=' . $file_encoded . '">' . $file . '</a><span class="date">' . gmdate(
        "H:i:s | d M",
        $key
      ) . '</span></li>';
  }

  return $output;
}

/**
 * Scan a directory and return an array of filenames.
 *
 * @param $data_path
 *
 * @return array
 *  An array containing the filenames present in the directory.
 */
function scan_directory($data_path) {
  $dir = opendir($data_path);
  $list = array();
  while ($file = readdir($dir)) {
    if ($file != '.' and $file != '..') {
      // add the filename, to be sure not to
      // overwrite a array key
      $mtime = filemtime($data_path . '/' . $file);
      $list[$mtime] = $file;
    }
  }
  closedir($dir);
  krsort($list);

  return $list;
}

/**
 * We will always call this function.
 */
function fakebox_init($dir) {
  // Populate the mail value.
  if (empty($_GET['mail'])) {
    // Get the last one.
    $scanned_directory = scan_directory($dir);

    $file = array_pop($scanned_directory);
    header('Location: /?mail=' . base64url_encode($file) . '');
  }

  // Also check if we want to delete something.
  delete_email($dir);
}

/**
 * Generate an HTML output that represent an e-mail.
 */
function display_email_content($dir) {
  if (!empty($_GET['mail'])) {
    $file = base64url_decode($_GET['mail']);
  }
  $email = file_get_contents($dir . '/' . $file);
  $parsed_mail = split_mail($email);

  $output = '';
  $output .= "<div class='headers'>";
  $output .= $parsed_mail['headers'];
  $output .= "</div>";
  $output .= '<h2>' . $parsed_mail['subject'] . '</h2>';
  $output .= _filter_url($parsed_mail['message']);

  return $output;
}

/**
 * Split part of an email into subject, headers and message.
 */
function split_mail($email) {
  // handle email
  $lines = explode(PHP_EOL, $email);

  // set empty vars and explode message to variables

  $headers = "";
  $message = "";
  $subject = "";
  $splittingheaders = TRUE;
  for ($i = 0; $i < count($lines); $i++) {
    if ($splittingheaders) {
      // this is a header
      $headers .= $lines[$i] . '<br />';
      if (preg_match("/^Subject: (.*)/", $lines[$i], $matches)) {
        $subject = $matches[1];
      }

    }
    else {
      // not a header, but message
      $message .= $lines[$i] . '<br />';
    }

    if (trim($lines[$i]) == "" && $splittingheaders) {
      // empty line, header section has ended
      $splittingheaders = FALSE;
    }
  }

  return array(
    'headers' => $headers,
    'subject' => $subject,
    'message' => $message
  );
}

/**
 * Return a HTML output that represent a "delete" link.
 */
function generate_delete_action() {
  if (!empty($_GET['mail'])) {
    $mail = $_GET['mail'];
    $output = '<a href="/?delete=' . $mail . '"> <img src="images/delete.png" /></a>';

    return $output;
  }
  else {
    return '';
  }
}

/**
 * Delete an email.
 */
function delete_email($dir) {
  if (!empty($_GET['delete'])) {
    $mail = $_GET['delete'];
    $file = base64url_decode($mail);
    unlink($dir . '/' . $file);
    header('Location: ' . '/');
  }
}

/**
 * <3 Drupal
 *
 * Parse a text and make all link clickable.
 */
function _filter_url($text) {
  // Tags to skip and not recurse into.
  $ignore_tags = 'a|script|style|code|pre';

  // Create an array which contains the regexps for each type of link.
  // The key to the regexp is the name of a function that is used as
  // callback function to process matches of the regexp. The callback function
  // is to return the replacement for the match. The array is used and
  // matching/replacement done below inside some loops.
  $tasks = array();

  // Prepare protocols pattern for absolute URLs.
  // check_url() will replace any bad protocols with HTTP, so we need to support
  // the identical list. While '//' is technically optional for MAILTO only,
  // we cannot cleanly differ between protocols here without hard-coding MAILTO,
  // so '//' is optional for all protocols.
  // @see filter_xss_bad_protocol()
  $protocols = array(
    'ftp',
    'http',
    'https',
    'irc',
    'mailto',
    'news',
    'nntp',
    'rtsp',
    'sftp',
    'ssh',
    'tel',
    'telnet',
    'webcal'
  );
  $protocols = implode(':(?://)?|', $protocols) . ':(?://)?';

  // Prepare domain name pattern.
  // The ICANN seems to be on track towards accepting more diverse top level
  // domains, so this pattern has been "future-proofed" to allow for TLDs
  // of length 2-64.
  $domain = '(?:[A-Za-z0-9._+-]+\.)?[A-Za-z]{2,64}\b';
  $ip = '(?:[0-9]{1,3}\.){3}[0-9]{1,3}';
  $auth = '[a-zA-Z0-9:%_+*~#?&=.,/;-]+@';
  $trail = '[a-zA-Z0-9:%_+*~#&\[\]=/;?!\.,-]*[a-zA-Z0-9:%_+*~#&\[\]=/;-]';

  // Prepare pattern for optional trailing punctuation.
  // Even these characters could have a valid meaning for the URL, such usage is
  // rare compared to using a URL at the end of or within a sentence, so these
  // trailing characters are optionally excluded.
  $punctuation = '[\.,?!]*?';

  // Match absolute URLs.
  $url_pattern = "(?:$auth)?(?:$domain|$ip)/?(?:$trail)?";
  $pattern = "`((?:$protocols)(?:$url_pattern))($punctuation)`";
  $tasks['_filter_url_parse_full_links'] = $pattern;

  // Match e-mail addresses.
  $url_pattern = "[A-Za-z0-9._-]{1,254}@(?:$domain)";
  $pattern = "`($url_pattern)`";
  $tasks['_filter_url_parse_email_links'] = $pattern;

  // Match www domains.
  $url_pattern = "www\.(?:$domain)/?(?:$trail)?";
  $pattern = "`($url_pattern)($punctuation)`";
  $tasks['_filter_url_parse_partial_links'] = $pattern;

  // Each type of URL needs to be processed separately. The text is joined and
  // re-split after each task, since all injected HTML tags must be correctly
  // protected before the next task.
  foreach ($tasks as $task => $pattern) {

    // Split at all tags; ensures that no tags or attributes are processed.
    $chunks = preg_split('/(<.+?>)/is', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    // PHP ensures that the array consists of alternating delimiters and
    // literals, and begins and ends with a literal (inserting NULL as
    // required). Therefore, the first chunk is always text:
    $chunk_type = 'text';
    // If a tag of $ignore_tags is found, it is stored in $open_tag and only
    // removed when the closing tag is found. Until the closing tag is found,
    // no replacements are made.
    $open_tag = '';

    for ($i = 0; $i < count($chunks); $i++) {
      if ($chunk_type == 'text') {
        // Only process this text if there are no unclosed $ignore_tags.
        if ($open_tag == '') {
          // If there is a match, inject a link into this chunk via the callback
          // function contained in $task.
          $chunks[$i] = preg_replace_callback($pattern, $task, $chunks[$i]);
        }
        // Text chunk is done, so next chunk must be a tag.
        $chunk_type = 'tag';
      }
      else {
        // Only process this tag if there are no unclosed $ignore_tags.
        if ($open_tag == '') {
          // Check whether this tag is contained in $ignore_tags.
          if (preg_match("`<($ignore_tags)(?:\s|>)`i", $chunks[$i], $matches)) {
            $open_tag = $matches[1];
          }
        }
        // Otherwise, check whether this is the closing tag for $open_tag.
        else {
          if (preg_match("`<\/$open_tag>`i", $chunks[$i], $matches)) {
            $open_tag = '';
          }
        }
        // Tag chunk is done, so next chunk must be text.
        $chunk_type = 'text';
      }
    }

    $text = implode($chunks);

  }

  return $text;
}


/**
 * Makes links out of absolute URLs.
 *
 * Callback for preg_replace_callback() within _filter_url().
 */
function _filter_url_parse_full_links($match) {
  // The $i:th parenthesis in the regexp contains the URL.
  $i = 1;
  $match[$i] = html_entity_decode($match[$i]);
  $caption = $match[$i];

  return '<a href="' . $match[$i] . '">' . $caption . '</a>' . $match[$i + 1];
}

/**
 * Makes links out of e-mail addresses.
 *
 * Callback for preg_replace_callback() within _filter_url().
 */
function _filter_url_parse_email_links($match) {
  // The $i:th parenthesis in the regexp contains the URL.
  $i = 0;
  $match[$i] = html_entity_decode($match[$i]);
  $caption = $match[$i];

  return '<a href="mailto:' . $match[$i] . '">' . $caption . '</a>';
}

/**
 * Makes links out of domain names starting with "www."
 *
 * Callback for preg_replace_callback() within _filter_url().
 */
function _filter_url_parse_partial_links($match) {
  // The $i:th parenthesis in the regexp contains the URL.
  $i = 1;
  $match[$i] = html_entity_decode($match[$i]);
  $caption = $match[$i];

  return '<a href="http://' . $match[$i] . '">' . $caption . '</a>' . $match[$i + 1];
}
