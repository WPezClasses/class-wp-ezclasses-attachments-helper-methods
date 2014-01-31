Class WP ezClasses Attachments Helper Methods
=============================================

Assorted helper methods for making life with WordPress attachments easier. 


The Methods
===========

- wp_get_attachment_path() - Pass in an attachment_id (via $arr_args['attachment_id']) and wp_get_attachment_path() will return the path to the attachment

- wp_get_attachment() - Pass in an attachment_id (via $arr_args['attachment_id']) and returns numerous attachment properties. See method for details.

- get_attached_file_ez() - Similar to WP's get_attached_file() but with some cool extras. Mainly you can specifiy which file size you want back (not just 'full').
