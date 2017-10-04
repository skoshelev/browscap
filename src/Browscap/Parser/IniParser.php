<?php
declare(strict_types=1);

namespace Browscap\Parser;

final class IniParser implements ParserInterface
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var bool
     */
    private $shouldSort = false;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $fileLines;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function setShouldSort(bool $shouldSort): void
    {
        $this->shouldSort = $shouldSort;
    }

    public function shouldSort(): bool
    {
        return $this->shouldSort;
    }

    /**
     * @return array
     */
    public function getParsed(): array
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getLinesFromFile(): array
    {
        $filename = $this->filename;

        if (!file_exists($filename)) {
            throw new \InvalidArgumentException("File not found: {$filename}");
        }

        return file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * @param string[] $fileLines
     */
    public function setFileLines(array $fileLines)
    {
        $this->fileLines = $fileLines;
    }

    /**
     * @return array
     */
    public function getFileLines(): array
    {
        if (!$this->fileLines) {
            $fileLines = $this->getLinesFromFile();
        } else {
            $fileLines = $this->fileLines;
        }

        return $fileLines;
    }

    /**
     * @throws \RuntimeException
     * @return array
     */
    public function parse(): array
    {
        $fileLines = $this->getFileLines();

        $data = [];

        $currentSection  = '';
        $currentDivision = '';

        for ($line = 0, $count = count($fileLines); $line < $count; ++$line) {
            $currentLine       = ($fileLines[$line]);
            $currentLineLength = strlen($currentLine);

            if ($currentLineLength === 0) {
                continue;
            }

            if (substr($currentLine, 0, 40) === ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;') {
                $currentDivision = trim(substr($currentLine, 41));
                continue;
            }

            // We only skip comments that *start* with semicolon
            if ($currentLine[0] === ';') {
                continue;
            }

            if ($currentLine[0] === '[') {
                $currentSection = substr($currentLine, 1, ($currentLineLength - 2));
                continue;
            }

            $bits = explode('=', $currentLine);

            if (count($bits) > 2) {
                throw new \RuntimeException("Too many equals in line: {$currentLine}, in Division: {$currentDivision}");
            }

            if (count($bits) < 2) {
                $bits[1] = '';
            }

            $data[$currentSection][$bits[0]]   = $bits[1];
            $data[$currentSection]['Division'] = $currentDivision;
        }

        if ($this->shouldSort()) {
            $data = $this->sortArrayAndChildArrays($data);
        }

        $this->data = $data;

        return $data;
    }

    /**
     * @param array $array
     *
     * @return array
     */
    private function sortArrayAndChildArrays(array $array): array
    {
        ksort($array);

        foreach (array_keys($array) as $key) {
            if (!is_array($array[$key]) || empty($array[$key])) {
                continue;
            }

            $array[$key] = $this->sortArrayAndChildArrays($array[$key]);
        }

        return $array;
    }
}
