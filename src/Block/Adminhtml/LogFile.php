<?php

namespace Corrivate\RestApiLogger\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class LogFile extends Template implements RendererInterface
{
    private File $fileDriver;
    private DirectoryList $directoryList;

    public function __construct( // @phpstan-ignore missingType.iterableValue
        File             $fileDriver,
        DirectoryList    $directoryList,
        Context          $context,
        array            $data = [],
        ?JsonHelper      $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->fileDriver = $fileDriver;
        $this->directoryList = $directoryList;
    }

    protected $_template = 'Corrivate_RestApiLogger::logfile.phtml';

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getFileSize(): int
    {
        $filePath = $this->directoryList->getPath(AppDirectoryList::LOG) . '/rest_api.log';
        return $this->fileDriver->isExists($filePath)
            ? (int)$this->fileDriver->stat($filePath)['size']
            : 0;
    }

    public function getFileSizeText(): string
    {
        $size = $this->getFileSize();
        if (!$size) {
            return "0 MB";
        }
        if ($size < 1024) {
            return $size . "B";
        }
        if ($size < 1048576) {
            return round($size / 1024, 2) . "KB";
        }
        if ($size < 1073741824) {
            return round($size / 1048576, 2) . "MB";
        }
        return round($size / 1073741824, 2) . "GB";
    }

    public function getDownloadUrl(): string
    {
        return $this->_urlBuilder->getUrl('restapilogger/log/download');
    }
}
