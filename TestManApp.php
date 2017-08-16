<?php
namespace axenox\TestMan;

use exface\Core\Interfaces\InstallerInterface;
use exface\SqlDataConnector\SqlSchemaInstaller;
use exface\Core\CommonLogic\Model\App;

class TestManApp extends App
{
    public function getInstaller(InstallerInterface $injected_installer = null)
    {
        $installer = parent::getInstaller($injected_installer);
        
        $schema_installer = new SqlSchemaInstaller($this->getNameResolver());
        $schema_installer->setDataConnection($this->getWorkbench()->model()->getObject('axenox.TestMan.FEATURE')->getDataConnection());
        $installer->addInstaller($schema_installer);
        
        return $installer;
    }
}
?>