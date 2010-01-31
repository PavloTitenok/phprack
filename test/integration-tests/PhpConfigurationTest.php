<?php

class PhpConfigurationTest extends PhpRack_Test
{

    public function testPhpVersionIsCorrect()
    {
        $this->assert->php->version
            ->atLeast('5.2');
            
        $this->assert
            ->isTrue(function_exists('lcfirst'))
            ->onSuccess("Method lcfirst() exists, it's PHP5.3 for sure")
            ->onFailure("Method lcfirst() is absent, it's PHP5.2 or older");
    }

    public function testPhpExtensionsExist()
    {
        $this->assert->php->extensions
            ->isLoaded('xsl')
            ->isLoaded('simplexml')
            ->isLoaded('fileinfo');
    }

}