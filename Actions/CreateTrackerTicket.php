<?php
namespace axenox\TestMan\Actions;

use exface\Core\Actions\CreateData;
use exface\Core\Exceptions\Actions\ActionInputInvalidObjectError;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\DataTypes\StringDataType;
use exface\Core\Interfaces\Tasks\TaskInterface;
use exface\Core\Interfaces\DataSources\DataTransactionInterface;
use exface\Core\Interfaces\Tasks\ResultInterface;
use exface\Core\Factories\ActionFactory;

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
    private $linkbackUrl = '';

    public function init()
    {
        parent::init();
        $this->setUndoable(false);
        $this->setInputObjectAlias('axenox.TestMan.TEST_LOG');
    }

    protected function perform(TaskInterface $task, DataTransactionInterface $transaction) : ResultInterface
    {
        $input = $this->getInputDataSheet($task);
        
        $description = $this->buildTicketBody(null, $input);
        $ticket_object = $this->getWorkbench()->model()->getObject($this->getApp()->getConfig()->getOption('TRACKER.TICKET_OBJECT_ALIAS'));
        $result_sheet = DataSheetFactory::createFromObject($ticket_object);
        // First add all direct ticket attributes
        foreach ($input->getColumns() as $col) {
            if (StringDataType::startsWith($col->getName(), 'TRACKER_TICKET__')) {
                $result_sheet->setCellValue(str_replace('TRACKER_TICKET__', '', $col->getName()), 0, $col->getCellValue(0));
            }
        }
        
        // Now the specially computed attributes
        $result_sheet->setCellValue('DESCRIPTION', 0, $description);
        // $connection = $this->getWorkbench()->model()->getObject('REDMINE.UPLOAD')->getDataConnection()->getCurrentConnection();
        /*
        foreach ($this->getWorkbench()->getContext()->getScopeWindow()->getContext('exface.Core.UploadContext')->getUploadedFilePaths() as $file) {
            // $request = $connection->post('uploads.json', array('body' => fopen($file, 'r'))); // 500
            // $request = $connection->post('uploads.json', array('headers' => ['debug' => true, 'Content-Type' => 'application/octet-stream'], 'body' => fopen($file, 'r'))); // 500
        }
        $this->getWorkbench()->getContext()->getScopeWindow()->getContext('exface.Core.UploadContext')->clearUploads();
        */
        $result_sheet->dataCreate();
        $new_ticket_id = $result_sheet->getUidColumn()->getCellValue(0);
        
        // Insert a reference to the created ticket into the input sheet and perform @author aka
        // regular create operation on it. 
        // IMPORTANT: ignore related attributes since we have attributes of the tracker ticket
        // in the same sheet!
        $this->setIgnoreRelatedObjectsInInputData(true);
        $task->setInputData($input->setCellValue('TRACKER_TICKET', 0, $new_ticket_id));
        $result = parent::perform($task, $transaction);
        
        // Replace the result message with a custom one
        $goToAction = ActionFactory::createFromString($this->getWorkbench(), 'axenox.TestMan.GoToTrackerTicket');
        $result->setMessage($this->translate('RESULT', ['%url%' => $goToAction->buildUrlFromDataSheet($input), '%ticket_id%' => $new_ticket_id]));
        return $result;
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
*TestCase*: "{$data->getCellValue('TEST_CASE__FEATURE__MODULE__SHORT_NAME', 0)}.{$data->getCellValue('TEST_CASE', 0)}":{$this->buildUrlLinkBack($data->getRow(0))}
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

    

    /**
     * 
     * @return string
     */
    public function getLinkbackUrl() : string
    {
        return $this->linkbackUrl;
    }

    /**
     * Define a template for the linkback URL from the created ticket.
     * 
     * You can use any column name available in the input data as placeholder: e.g.
     * http://www.me.com/exface.testman.test-cases.html?filter_UID=[#TEST_CASE#]
     * 
     * @uxon-property linkback_url
     * @uxon-type string
     * 
     * @param string $url
     * @return CreateTrackerTicket
     */
    public function setLinkbackUrl(string $url) : CreateTrackerTicket
    {
        $this->linkbackUrl = $url;
        return $this;
    }
    
    /**
     * 
     * @param array $dataRow
     * @return string
     */
    protected function buildUrlLinkBack(array $dataRow) : string
    {
        return StringDataType::replacePlaceholders($this->getLinkbackUrl(), $dataRow);
    }

}
?>