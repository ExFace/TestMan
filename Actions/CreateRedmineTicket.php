<?php
namespace axenox\TestMan\Actions;

use exface\Core\Actions\CreateData;
use exface\Core\Exceptions\Actions\ActionInputInvalidObjectError;
use exface\Core\Factories\DataSheetFactory;

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
        
        $description = $this->buildTicketBody(null, $input);
        $ticket_object = $this->getWorkbench()->model()->getObject('axenox.RedmineConnector.ISSUE');
        $result = DataSheetFactory::createFromObject($ticket_object);
        // First add all direct ticket attributes
        foreach ($input->getColumns() as $col) {
            if (strpos($col->getName(), 'REDMINE_TICKET__') === 0) {
                $result->setCellValue(str_replace('REDMINE_TICKET__', '', $col->getName()), 0, $col->getCellValue(0));
            }
        }
        
        // Now the specially computed attributes
        $result->setCellValue('DESCRIPTION', 0, $description);
        // $connection = $this->getWorkbench()->model()->getObject('REDMINE.UPLOAD')->getDataConnection()->getCurrentConnection();
        foreach ($this->getWorkbench()->context()->getScopeWindow()->getContext('exface.Core.UploadContext')->getUploadedFilePaths() as $file) {
            // $request = $connection->post('uploads.json', array('body' => fopen($file, 'r'))); // 500
            // $request = $connection->post('uploads.json', array('headers' => ['debug' => true, 'Content-Type' => 'application/octet-stream'], 'body' => fopen($file, 'r'))); // 500
        }
        
        $this->getWorkbench()->context()->getScopeWindow()->getContext('exface.Core.UploadContext')->clearUploads();
        $result->dataCreate();
        $new_ticket_id = $result->getUidColumn()->getCellValue(0);
        
        // Now save a reference for the newly created ticket for the test case
        $ticket_ref = DataSheetFactory::createFromObjectIdOrAlias($this->getWorkbench(), 'axenox.TestMan.TICKET');
        $ticket_ref->setCellValue('TEST_CASE', 0, $input->getCellValue('TEST_CASE', 0));
        $ticket_ref->setCellValue('REDMINE_TICKET', 0, $new_ticket_id);
        $ticket_ref->dataCreate();
        
        // Create a history-entry with a reference to the ticket
        $history = DataSheetFactory::createFromObjectIdOrAlias($this->getWorkbench(), 'axenox.TestMan.TESTLAUF');
        $history->setCellValue('TEST_CASE', 0, $input->getCellValue('TEST_CASE', 0));
        $history->setCellValue('COMMENT', 0, $input->getCellValue('REDMINE_TICKET__SUBJECT', 0));
        $history->setCellValue('TICKET', 0, $ticket_ref->getCellValue('UID', 0));
        $history->setCellValue('TESTED_INSTALLATION', 0, $input->getCellValue('SYSTEM', 0));
        $history->setCellValue('TEST_DESCRIPTION', 0, $input->getCellValue('TESTABLAUF', 0));
        $history->dataCreate();
        
        $this->setResultDataSheet($result);
        $this->setResult('');
        $this->setResultMessage('New ticket <a target="_blank" href="' . $ticket_object->getDataConnection()->getUrl() . "issues/" . $new_ticket_id . '">#' . $new_ticket_id . '</a> created!');
        return;
    }
    
    protected function buildTicketBody($template, $data){
        if ($exception = $data->getCellValue('EXCEPTION', 0)) {
            $exception = '
					+*Exception:*+
					<pre style="height: 150px; overflow: scroll;">' . $exception . '</pre>';
        }
        
        if ($soll = $data->getCellValue('SOLL', 0)) {
            $soll = '+*SOLL:*+
					' . $soll;
        }
        
        $today = date("d.m.Y");
        
        return <<<TEXT
+*Testnotiz:*+
*TestCase*: "{$data->getCellValue('TEST_CASE__FEATURE__MODULE__SHORT_NAME', 0)}.{$data->getCellValue('TEST_CASE__UID', 0)}":http://sdrexf1.salt-solutions.de/exface/exface.testman.testfaelle-ui.html?fltr00_UID={$data->getCellValue('TEST_CASE__UID', 0)}
*AID:* {$data->getCellValue('TEST_CASE__FEATURE__MODULE__SHORT_NAME', 0)} {$data->getCellValue('TEST_CASE__FEATURE__MODULE__NAME', 0)}
*Dialog/Menü:* {$data->getCellValue('TEST_CASE__FEATURE__MENU', 0)}
*Testsystem:* {$data->getCellValue('SYSTEM', 0)}, Klient {$data->getCellValue('MANDANT', 0)}, getestet in Version: {$today} {$data->getCellValue('VERSION', 0)}
*DB-Nutzer/Schema:* {$data->getCellValue('DBSCHEMA', 0)}

+*Testablauf:*+
{$data->getCellValue('TESTABLAUF', 0)}

{$exception}

{$soll}

---------------------------

+*Testergebnisse*+

{$today} *fehlgeschlagen*

TEXT;
    }
}
?>