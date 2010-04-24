<?php
require_once PHPRACK_PATH . '/Mail/Transport/Smtp.php';

require_once PHPRACK_PATH . '/Mail/Transport/Sendmail.php';

class phpRack_Mail
{
    /**
     * Constructor closed by default
     */
    private function __construct()
    {}

    /**
     * Factory method to run sendmail or smtp. Depends on options
     *
     * @param array List of parameters
     * @return phpRack_Mail
     * @see phpRack_Mail_Transport_Smtp
     * @see phpRack_Mail_Transport_Sendmail
     */
    public static function factory(array $params)
    {
        if (is_array($params['smtp']) && count($params['smtp'])) {
            return new phpRack_Mail_Transport_Smtp($params);
        }
        return new phpRack_Mail_Transport_Sendmail($params);
    }
}
