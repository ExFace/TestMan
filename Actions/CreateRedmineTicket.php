<?php
namespace axenox\TestMan\Actions;

use exface\Core\Actions\CreateData;
use exface\Core\Exceptions\Actions\ActionInputInvalidObjectError;

/**
 * This action switches on the record mode in the ActionTest context
 *
 * @author Andrej Kabachnik
 *        
 */
class CreateRedmineTicket extends CreateData
{

    public function init()
    {
        parent::init();
        $this->setUndoable(false);
    }

    protected function perform()
    {
        $input = $this->getInputDataSheet();
        
        // Check if the input is OK
        if (!$input->getMetaObject()->is('axenox.TestMan.TICKET')) {
            throw new ActionInputInvalidObjectError($this, 'Only TestMan tickets are accepted as input for "' . $this->getAliasWithNamespace() . '": "' . $input->getMetaObject()->getAliasWithNamespace() . '" given instead!', '6T5DMUS');
        }
        
        if ($exception = $input->getCellValue('EXCEPTION', 0)) {
            $exception = '
					+*Exception:*+
					<pre style="height: 150px; overflow: scroll;">' . $exception . '</pre>';
        }
        
        if ($soll = $input->getCellValue('SOLL', 0)) {
            $soll = '+*SOLL:*+
					' . $soll;
        }
        
        $today = date("d.m.Y");
        
        $description = <<<TEXT
+*Testnotiz:*+ 
*AID:* {$input->getCellValue('TEST_CASE__FEATURE__MODULE__SHORT_NAME', 0)} {$input->getCellValue('TEST_CASE__FEATURE__MODULE__NAME', 0)}
*Dialog/Menü:* {$input->getCellValue('TEST_CASE__FEATURE__MENU', 0)}
*Testsystem:* {$input->getCellValue('SYSTEM', 0)}alexa, Klient {$input->getCellValue('MANDANT', 0)}, getestet in Version:
{$today} {$input->getCellValue('VERSION', 0)}
*DB-Nutzer/Schema:* {$input->getCellValue('DBSCHEMA', 0)}
		
+*Testablauf:*+ 
{$input->getCellValue('TESTABLAUF', 0)}

{$exception}

{$soll}
TEXT;
        $ticket_object = $this->getWorkbench()->model()->getObject('axenox.RedmineConnector.ISSUE');
        $result = $this->getWorkbench()->data()->createDataSheet($ticket_object);
        // First add all direct ticket attributes
        foreach ($input->getColumns() as $col) {
            if (strpos($col->getName(), 'REDMINE_TICKET__') === 0) {
                $result->setCellValue(str_replace('REDMINE_TICKET__', '', $col->getName()), 0, $col->getCellValue(0));
            }
        }
        
        // Now the specially computed attributes
        $result->setCellValue('DESCRIPTION', 0, $description);
        // $connection = $this->getWorkbench()->model()->getObject('REDMINE.UPLOAD')->getDataConnection()->getCurrentConnection();
        foreach ($this->getWorkbench()->context()->getScopeWindow()->getContext('Upload')->getUploadedFilePaths() as $file) {
            // $request = $connection->post('uploads.json', array('body' => fopen($file, 'r'))); // 500
            // $request = $connection->post('uploads.json', array('headers' => ['debug' => true, 'Content-Type' => 'application/octet-stream'], 'body' => fopen($file, 'r'))); // 500
        }
        
        $this->getWorkbench()->context()->getScopeWindow()->getContext('Upload')->clearUploads();
        $new_rows = $result->dataCreate();
        
        if ($new_rows > 0) {
            // The following is a workaround for a strange bug, that returns an Error 500 upon creating a ticket instead of a ticket number.
            // The idea is simply to fetch the most recent ticket of the current user.
            // IDEA This workaround should be removed once the redmine bug is fixed
            $check = $this->getWorkbench()->data()->createDataSheet($ticket_object);
            $check->addFilterFromString('AUTHOR', $ticket_object->getDataConnection()->getUserId());
            $check->getColumns()->addFromExpression($ticket_object->getUidAlias());
            $check->dataRead();
            $new_ticket_id = $check->getCellValue($ticket_object->getUidAlias(), 0);
        } else {
            $new_ticket_id = $result->getUidColumn()->getCellValue(0);
        }
        
        // Now save a reference for the newly created ticket for the test case
        $ticket_ref = $this->getWorkbench()->data()->createDataSheet($this->getWorkbench()->model()->getObject('axenox.TestMan.TICKET'));
        $ticket_ref->setCellValue('TEST_CASE', 0, $input->getCellValue('TEST_CASE', 0));
        $ticket_ref->setCellValue('REDMINE_TICKET', 0, $new_ticket_id);
        $ticket_ref->dataCreate();
        
        $this->setResultDataSheet($result);
        $this->setResult('');
        $this->setResultMessage('New ticket <a target="_blank" href="' . $ticket_object->getDataConnection()->getUrl() . "issues/" . $new_ticket_id . '">#' . $new_ticket_id . '</a> created!');
        return;
    }
}
?>