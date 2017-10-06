<?php
namespace axenox\TestMan\Actions;

use exface\Core\Actions\CreateData;
use exface\Core\Exceptions\Actions\ActionInputInvalidObjectError;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\DataTypes\StringDataType;

/**
 * Creates a ticket in the issue tracker for a given test log entry.
 * 
 * This action requires the following configuration options:
 * - TRACKER.GOTO_TICKET_URL
 * - TRACKER.TICKET_OBJECT_ALIAS
 *
 * @author Andrej Kabachnik
 *        
 */
class CreateTrackerTicket extends CreateData
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
        if (!$input->getMetaObject()->is('axenox.TestMan.TEST_LOG')) {
            throw new ActionInputInvalidObjectError($this, 'Only TestMan TEST_LOG entries are accepted as input for "' . $this->getAliasWithNamespace() . '": "' . $input->getMetaObject()->getAliasWithNamespace() . '" given instead!', '6T5DMUS');
        }
        
        $description = $this->buildTicketBody(null, $input);
        $ticket_object = $this->getWorkbench()->model()->getObject($this->getApp()->getConfig()->getOption('TRACKER.TICKET_OBJECT_ALIAS'));
        $result = DataSheetFactory::createFromObject($ticket_object);
        // First add all direct ticket attributes
        foreach ($input->getColumns() as $col) {
            if (StringDataType::startsWith($col->getName(), 'TRACKER_TICKET__')) {
                $result->setCellValue(str_replace('TRACKER_TICKET__', '', $col->getName()), 0, $col->getCellValue(0));
            }
        }
        
        // Now the specially computed attributes
        $result->setCellValue('DESCRIPTION', 0, $description);
        // $connection = $this->getWorkbench()->model()->getObject('REDMINE.UPLOAD')->getDataConnection()->getCurrentConnection();
        /*
        foreach ($this->getWorkbench()->context()->getScopeWindow()->getContext('exface.Core.UploadContext')->getUploadedFilePaths() as $file) {
            // $request = $connection->post('uploads.json', array('body' => fopen($file, 'r'))); // 500
            // $request = $connection->post('uploads.json', array('headers' => ['debug' => true, 'Content-Type' => 'application/octet-stream'], 'body' => fopen($file, 'r'))); // 500
        }
        $this->getWorkbench()->context()->getScopeWindow()->getContext('exface.Core.UploadContext')->clearUploads();
        */
        $result->dataCreate();
        $new_ticket_id = $result->getUidColumn()->getCellValue(0);
        
        // Insert a reference to the created ticket into the input sheet and perform @author aka
        // regular create operation on it. 
        // IMPORTANT: ignore related attributes since we have attributes of the tracker ticket
        // in the same sheet!
        $this->setIgnoreRelatedObjectsInInputData(true);
        $this->setInputDataSheet($input->setCellValue('TRACKER_TICKET', 0, $new_ticket_id));
        parent::perform();
        
        // Replace the result message with a custom one
        $this->setResultMessage($this->translate('RESULT', ['%url%' => $this->getApp()->getAction('GoToTrackerTicket')->buildUrlFromDataSheet($input), '%ticket_id%' => $new_ticket_id]));
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
*TestCase*: "{$data->getCellValue('TEST_CASE__FEATURE__MODULE__SHORT_NAME', 0)}.{$data->getCellValue('TEST_CASE', 0)}":http://sdrexf1.salt-solutions.de/exface/exface.testman.testfaelle-ui.html?fltr00_UID={$data->getCellValue('TEST_CASE', 0)}
*AID:* {$data->getCellValue('TEST_CASE__FEATURE__MODULE__SHORT_NAME', 0)} {$data->getCellValue('TEST_CASE__FEATURE__MODULE__NAME', 0)}
*Dialog/Menü:* {$data->getCellValue('TEST_CASE__FEATURE__MENU', 0)}
*Testsystem:* {$data->getCellValue('TESTED_INSTALLATION', 0)}, Klient {$data->getCellValue('MANDANT', 0)}, getestet in Version: {$today} {$data->getCellValue('TESTED_VERSION', 0)}
*DB-Nutzer/Schema:* {$data->getCellValue('DBSCHEMA', 0)}

+*Testablauf:*+
{$data->getCellValue('TEST_DESCRIPTION', 0)}

{$exception}

{$soll}

---------------------------

+*Testergebnisse*+

{$today} *fehlgeschlagen*

TEXT;
    }
}
?>