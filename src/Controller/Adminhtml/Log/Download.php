<?php

namespace Corrivate\RestApiLogger\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\Filesystem\Driver\File;

class Download extends Action
{
    public const ADMIN_RESOURCE = 'Corrivate_RestApiLogger::download_log';

    private FileFactory $fileFactory;
    private DirectoryList $directoryList;
    private File $fileDriver;

    public function __construct(
        Action\Context $context,
        FileFactory    $fileFactory,
        DirectoryList  $directoryList,
        File           $fileDriver
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
    }

    public function execute()
    {
        $fileName = 'rest_api.log';
        $filePath = $this->directoryList->getPath(AppDirectoryList::LOG) . "/$fileName";
        if (!$this->fileDriver->isExists($filePath)) {
            $this->messageManager->addErrorMessage(__('File does not exist.'));
            return $this->_redirect('adminhtml/dashboard');
        }

        return $this->fileFactory->create(
            $fileName,
            [
                'type' => 'filename',
                'value' => $filePath,
                'rm' => false
            ],
            AppDirectoryList::VAR_DIR,
            'application/octet-stream'
        );
    }
}
