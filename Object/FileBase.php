<?php
namespace Newageerp\SfFiles\Object;

use Doctrine\ORM\Mapping as ORM;
use Newageerp\SfFiles\Interface\IFile;

class FileBase implements IFile
{
    /**
     * @ORM\Column(type="string")
     */
    protected string $fileName;
    /**
     * @ORM\Column(type="string")
     */
    protected string $orgFileName;
    /**
     * @ORM\Column(type="string")
     */
    protected string $folder;
    /**
     * @ORM\Column(type="string")
     */
    protected string $path;
    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $deleted = false;
    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $appproved = false;

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getOrgFileName(): string
    {
        return $this->orgFileName;
    }

    /**
     * @param string $orgFileName
     */
    public function setOrgFileName(string $orgFileName): void
    {
        $this->orgFileName = $orgFileName;
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    /**
     * @param string $folder
     */
    public function setFolder(string $folder): void
    {
        $this->folder = $folder;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     */
    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    /**
     * @return bool
     */
    public function isAppproved(): bool
    {
        return $this->appproved;
    }

    /**
     * @param bool $appproved
     */
    public function setAppproved(bool $appproved): void
    {
        $this->appproved = $appproved;
    }
}