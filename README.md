# Wowza for CumulusClips

This plugin helps integrate Wowza Streaming Engine with CumulusClips. Much of this code was adapted from work originally done by Wes Wright.  

## Requirements

* Wowza Streaming Engine

The following tools allow us to automatically manage file upload paths for the encoder, based on user:

* LDAP for CumulusClips
* AuthCAS for CumulusClips 

## Installation 

Since we are mounting Wowza content directories outside of the default CumulusClips directories, we need to somehow set the `UPLOAD_PATH` constant when the application loads.  To minimize changes to the core application code, the `UPLOAD_PATH` setting in bootstrap.php has been moved down below the app.start plugin hook, where it is assigned from a variable that can be set as needed.

For example, removing:

```
define('UPLOAD_PATH', DOC_ROOT . '/cc-content/uploads');
```

adding this to the config settings in that file:

```
$config->default_upload_path = DOC_ROOT . '/cc-content/uploads';
```

and then the following to the bottom of the file will give you a more flexible upload path:

```
define('UPLOAD_PATH', $config->default_upload_path);
```
 
