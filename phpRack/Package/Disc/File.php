<?php
/**
 * phpRack: Integration Testing Framework
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt. It is also available 
 * through the world-wide-web at this URL: http://www.phprack.com/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@phprack.com so we can send you a copy immediately.
 *
 * @copyright Copyright (c) phpRack.com
 * @version $Id$
 * @category phpRack
 */

/**
 * @see phpRack_Package
 */

require_once PHPRACK_PATH . '/Package.php';

/**
 * File informations and content
 *
 * @package Tests
 */
class phpRack_Package_Disc_File extends phpRack_Package
{
    /**
    * Buffer used is tail function to read blocks from file end
    */
    const READ_BUFFER_SIZE = 1024;

    /**
     * Check that file exists
     *
     * @param string File name to check
     * @return boolean True if file exists
     */
    protected function _isFileExists($fileName)
    {
        if (!file_exists($fileName)) {
            $this->_failure("File {$fileName} is not found");
            return false;
        }

        return true;
    }

    /**
     * Show the content of the file
     *
     * @param string File name to display
     * @return $this
     */
    public function cat($fileName)
    {
        $fileName = $this->_convertFileName($fileName);

        // Check that file exists
        if (!$this->_isFileExists($fileName)) {
            return $this;
        }

        $this->_log(file_get_contents($fileName));
            
        return $this;
    }

    /**
     * Show last x lines from the file
     *
     * @param string File name
     * @param string How many lines to display?
     * @return $this
     */
    public function tail($fileName, $linesCount)
    {
        $fileName = $this->_convertFileName($fileName);

        // Check that file exists
        if (!$this->_isFileExists($fileName)) {
            return $this;
        }

        // Open file and move pointer to end of file
        $fp = fopen($fileName, 'rb');
        fseek($fp, 0, SEEK_END);

        // Read offset of end of file
        $offset = ftell($fp);
        $content = '';

        do {
            // Move file pointer for new read
            $offset = max(0, $offset - self::READ_BUFFER_SIZE);
            fseek($fp, $offset, SEEK_SET);

            $readBuffer = fread($fp, self::READ_BUFFER_SIZE);
            $linesCountInReadBuffer = substr_count($readBuffer, "\n");

            // If we have enought lines extract from last readed fragment only required lines
            if ($linesCountInReadBuffer >= $linesCount) {
                $readBuffer = implode("\n", array_slice(explode("\n", $readBuffer), -$linesCount));
            }

            // Update how many lines still need to be readed
            $linesCount -= $linesCountInReadBuffer;

            // Attach last readed lines at beggining of earlier readed fragments
            $content = $readBuffer . $content;
        } while($offset > 0 && $linesCount > 0);

        $this->_log($content);
        return $this;
    }

    /**
     * Show first x lines from the file
     *
     * @param string File name
     * @param string How many lines to display?
     * @return $this
     */
    public function head($fileName, $linesCount)
    {
        $fileName = $this->_convertFileName($fileName);

        // Check that file exists
        if (!$this->_isFileExists($fileName)) {
            return $this;
        }

        $content = '';
        $readedLinesCount = 0;
        $fp = fopen($fileName, 'rb');

        // Read line by line until we have required count or we reach EOF
        while ($readedLinesCount < $linesCount && !feof($fp)) {
            $content .= fgets($fp);
            $readedLinesCount++;
        }

        fclose($fp);
        $this->_log($content);
        return $this;
    }
}
