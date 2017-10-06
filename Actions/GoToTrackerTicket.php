<?php
namespace axenox\TestMan\Actions;

use exface\Core\Actions\GoToUrl;

/**
 * Navigates to a URL showing the ticket referenced in the input data in the issue tracker.
 * 
 * This action uses TRACKER.GOTO_TICKET_URL configuration option.
 *
 * @author Andrej Kabachnik
 *        
 */
class GoToTrackerTicket extends GoToUrl
{
    protected function init()
    {
        $this->setOpenInNewWindow(true);
    }
    
    public function getUrl()
    {
        return $this->getApp()->getConfig()->getOption('TRACKER.GOTO_TICKET_URL');
    }
}
?>