<?php
namespace Newageerp\SfFiles\Interface;

interface IFile
{
    /**
     * @return string
     */
    public function getFileName(): string;

    /**
     * @param string $fileName
     */
    public function setFileName(string $fileName): void;

    /**
     * @return string
     */
    public function getOrgFileName(): string;

    /**
     * @param string $orgFileName
     */
    public function setOrgFileName(string $orgFileName): void;

    /**
     * @return string
     */
    public function getFolder(): string;

    /**
     * @param string $folder
     */
    public function setFolder(string $folder): void;

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @param string $path
     */
    public function setPath(string $path): void;

    /**
     * @return bool
     */
    public function isDeleted(): bool;

    /**
     * @param bool $deleted
     */
    public function setDeleted(bool $deleted): void;

    /**
     * @return bool
     */
    public function isAppproved(): bool;

    /**
     * @param bool $appproved
     */
    public function setAppproved(bool $appproved): void;
}