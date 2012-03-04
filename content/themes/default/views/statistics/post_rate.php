<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

$data = json_decode($data, TRUE);
?>

<table class="bordered-table" style="width:600px; margin: 10px auto;">
	<thead>
		<tr>
			<th><?php echo _('Posts within Last Hour') ?></th>
			<th><?php echo _('Posts per Minute') ?></th>
		</tr>
	</thead>
	<tbody>

		<td><?php echo $data[0]['COUNT(*)']; ?></td>
		<td><?php echo $data[0]['COUNT(*)/60']; ?></td>
	</tbody>
</table>