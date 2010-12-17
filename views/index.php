<h3>Select a channel for export</h3>

<?php echo form_open( $form_action ); ?>

<p>
	<select name="channel_id">
<?php foreach( $channels as $channel_id => $channel_title ): ?>
		<option value="<?php echo $channel_id; ?>"><?php echo $channel_title ?></option>
<?php endforeach; ?>
	</select>

	<?php echo form_submit(array('name' => 'submit', 'value' => 'Export in CSV format', 'class' => 'submit'));?>
</p>



<?php echo form_close();
