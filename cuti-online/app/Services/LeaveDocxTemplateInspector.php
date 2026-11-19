<?php

namespace App\Services;

use XMLReader;
use ZipArchive;

class LeaveDocxTemplateInspector
{
    public const REJECTION_MESSAGE = 'Template harus berupa DOCX yang valid dan tidak memuat referensi eksternal.';

    public function accepts(string $path): bool
    {
        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }

        try {
            if ($archive->numFiles > 2000
                || $archive->locateName('[Content_Types].xml') === false
                || $archive->locateName('word/document.xml') === false) {
                return false;
            }

            $xmlBytes = 0;
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $part = $archive->statIndex($index);
                if ($part === false) {
                    return false;
                }

                $name = strtolower($part['name']);
                $isRelationships = str_ends_with($name, '.rels');
                if (! $isRelationships && ! str_ends_with($name, '.xml')) {
                    continue;
                }

                $xmlBytes += $part['size'];
                if ($part['size'] > 25 * 1024 * 1024 || $xmlBytes > 80 * 1024 * 1024) {
                    return false;
                }

                $xml = $archive->getFromIndex($index);
                if ($xml === false || ! $this->acceptsXml($xml, $isRelationships)) {
                    return false;
                }
            }

            return true;
        } finally {
            $archive->close();
        }
    }

    private function acceptsXml(string $xml, bool $isRelationships): bool
    {
        if ($xml === '') {
            return false;
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $reader = new XMLReader;

        try {
            if (! $reader->XML($xml, null, LIBXML_NONET)) {
                return false;
            }

            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::DOC_TYPE) {
                    return false;
                }

                if ($isRelationships && $reader->nodeType === XMLReader::ELEMENT
                    && $reader->localName === 'Relationship' && ! $this->hasInternalTarget($reader)) {
                    return false;
                }
            }

            return libxml_get_last_error() === false;
        } finally {
            $reader->close();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function hasInternalTarget(XMLReader $reader): bool
    {
        $mode = $reader->getAttribute('TargetMode') ?? 'Internal';
        $target = trim($reader->getAttribute('Target') ?? '');

        return $mode === 'Internal'
            && $target !== ''
            && ! str_contains($target, '\\')
            && preg_match('~^(?:[a-z][a-z0-9+.-]*:|//)~i', $target) === 0;
    }
}
