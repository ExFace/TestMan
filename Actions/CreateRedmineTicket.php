<?php namespace axenox\TestMan\Actions;

use exface\Core\Actions\CreateData;
use exface\Core\Exceptions\ActionRuntimeException;
use exface\Core\Exceptions\Actions\ActionInputInvalidObjectError;

/**
 * This action switches on the record mode in the ActionTest context
 * 
 * @author Andrej Kabachnik
 *
 */
class CreateRedmineTicket extends CreateData {
	
	public function init() {
		parent::init();
		$this->set_undoable(false);
	}
	
	protected function perform(){
		$input = $this->get_input_data_sheet();
		
		// Check if the input is OK
		if (strcasecmp($input->get_meta_object()->get_alias_with_namespace(), 'exface.TestMan.TICKET') !== 0){
			throw new ActionInputInvalidObjectError($this, 'Only TestMan tickets are accepted as input for "' . $this->get_alias_with_namespace() . '": "' . $input->get_meta_object()->get_alias_with_namespace() . '" given instead!', '6T5DMUS');
		}
		
		if ($exception = $input->get_cell_value('EXCEPTION', 0)){
			$exception = '
					+*Exception:*+
					<pre style="height: 150px; overflow: scroll;">' . $exception . '</pre>';
		}
		
		if ($soll = $input->get_cell_value('SOLL', 0)){
			$soll = '+*SOLL:*+
					' . $soll;
		}
		
		$today = date("d.m.Y");
		
		$description = <<<TEXT
+*Testnotiz:*+ 
*AID:* {$input->get_cell_value('TEST_CASE__FEATURE__MODULE__SHORT_NAME', 0)} {$input->get_cell_value('TEST_CASE__FEATURE__MODULE__NAME', 0)}
*Dialog/Menü:* {$input->get_cell_value('TEST_CASE__FEATURE__MENU', 0)}
*Testsystem:* {$input->get_cell_value('SYSTEM', 0)}alexa, Klient {$input->get_cell_value('MANDANT', 0)}, getestet in Version:
{$today} {$input->get_cell_value('VERSION', 0)}
*DB-Nutzer/Schema:* {$input->get_cell_value('DBSCHEMA', 0)}
		
+*Testablauf:*+ 
{$input->get_cell_value('TESTABLAUF', 0)}

{$exception}

{$soll}
TEXT;
		$ticket_object = $this->get_workbench()->model()->get_object('axenox.RedmineConnector.ISSUE');
		$result = $this->get_workbench()->data()->create_data_sheet($ticket_object);
		// First add all direct ticket attributes
		foreach ($input->get_columns() as $col){
			if (strpos($col->get_name(), 'REDMINE_TICKET__') === 0){
				$result->set_cell_value(str_replace('REDMINE_TICKET__', '', $col->get_name()), 0, $col->get_cell_value(0));
			}
		}

		// Now the specially computed attributes
		$result->set_cell_value('DESCRIPTION', 0, $description);
		//$connection = $this->get_workbench()->model()->get_object('REDMINE.UPLOAD')->get_data_connection()->get_current_connection();
		foreach ($this->get_workbench()->context()->get_scope_window()->get_context('Upload')->get_uploaded_file_paths() as $file){
			// $request = $connection->post('uploads.json', array('body' => fopen($file, 'r'))); // 500
			// $request = $connection->post('uploads.json', array('headers' => ['debug' => true, 'Content-Type' =>  'application/octet-stream'], 'body' => fopen($file, 'r'))); // 500
		}
		
		$this->get_workbench()->context()->get_scope_window()->get_context('Upload')->clear_uploads();
		$result->data_create();
		
		// The following is a workaround for a strange bug, that returns an Error 500 upon creating a ticket instead of a ticket number.
		// The idea is simply to fetch the most recent ticket of the current user.
		// IDEA This workaround should be removed once the redmine bug is fixed
		$check = $this->get_workbench()->data()->create_data_sheet($ticket_object);
		$check->add_filter_from_string('AUTHOR', $ticket_object->get_data_connection()->get_user_id());
		$check->get_columns()->add_from_expression($ticket_object->get_uid_alias());
		$check->data_read();
		$new_ticket_id = $check->get_cell_value($ticket_object->get_uid_alias(), 0);
		
		// Now save a reference for the newly created ticket for the test case
		$ticket_ref = $this->get_workbench()->data()->create_data_sheet($this->get_workbench()->model()->get_object('EXFACE.TESTMAN.TICKET'));
		$ticket_ref->set_cell_value('TEST_CASE', 0, $input->get_cell_value('TEST_CASE', 0));
		$ticket_ref->set_cell_value('REDMINE_TICKET', 0, $new_ticket_id);
		$ticket_ref->data_create();
		
		$this->set_result_data_sheet($result);
		$this->set_result_message('New ticket <a target="_blank" href="' . $ticket_object->get_data_connection()->get_url() . "issues/" . $new_ticket_id . '">#' . $new_ticket_id . '</a> created!');
		return;
	}
}
?>