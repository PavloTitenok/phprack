<?php
require PHPRACK_PATH . '/Mail/Transport/Abstract.php';

class phpRack_Mail_Transport_Sendmail extends phpRack_Mail_Transport_Abstract
{
    public function send()
    {
        return mail($this->_to[0],
                    $this->_getEncodedSubject(),
                    $this->_getEncodedSubject(),
                    $this->_getHeaders());
    }

    private function _getHeaders()
    {
        $headers = 'To: ' . $this->_to[0] . "\r\n";

        $count = count($this->_to);
        if ($count > 1) {
            for ($i=1;$i<$count;$i++) {
                $headers .= 'Cc: ' . $this->_to[$i];
            }
        }

        $headers .= 'From: ' . $this->_from . "\r\n";
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
        $headers .= 'Content-transfer-encoding: base64' . "\r\n";
        return $headers;
    }
}