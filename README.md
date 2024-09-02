# opensim_RemoteAdmin
To update the provided `RemoteAdmin.php` code to be compatible with PHP 8, we need to make a few adjustments. Specifically, we'll modernize the constructor method (which in older PHP versions shared the same name as the class), handle any potential deprecations, and improve overall security and efficiency. Here's the revised version:

```php
<?php

// How to instantiate a RemoteAdmin object ?
// $myremoteadmin = new RemoteAdmin("mySimulatorURL", Port, "secret password")

// How to send commands to remoteadmin plugin ?
// $myremoteadmin->SendCommand('admin_broadcast', array('message' => 'Message to broadcast to all regions'));
// $myremoteadmin->SendCommand('admin_shutdown');
// Commands like admin_shutdown don't need params, so you can leave the second SendCommand function param empty ;)

// Example for error handling
// 
// include('classes/RemoteAdmin.php');
// $RA = new RemoteAdmin('localhost', 9000, 'secret');
// $retour = $RA->SendCommand('admin_shutdown');
// if ($retour === FALSE)
// {
//  echo 'ERROR';
// }

class RemoteAdmin
{
    private string $simulatorURL;
    private int $simulatorPort;
    private string $password;

    public function __construct(string $sURL, int $sPort, string $pass)
    {
        $this->simulatorURL = $sURL;
        $this->simulatorPort = $sPort;
        $this->password = $pass;
    }

    public function SendCommand(string $command, array $params = []): array|false
    {
        $paramsNames = array_keys($params);
        $paramsValues = array_values($params);

        // Building the XML data to pass to RemoteAdmin through XML-RPC ;)
        $xml = '<methodCall>
                    <methodName>' . htmlspecialchars($command, ENT_XML1, 'UTF-8') . '</methodName>
                    <params>
                        <param>
                            <value>
                                <struct>
                                    <member>
                                        <name>password</name>
                                        <value><string>' . htmlspecialchars($this->password, ENT_XML1, 'UTF-8') . '</string></value>
                                    </member>';

        foreach ($params as $name => $value) {
            $xml .= '<member>
                        <name>' . htmlspecialchars($name, ENT_XML1, 'UTF-8') . '</name>
                        <value><string>' . htmlspecialchars($value, ENT_XML1, 'UTF-8') . '</string></value>
                     </member>';
        }

        $xml .= '                </struct>
                            </value>
                        </param>
                    </params>
                </methodCall>';

        // Now building headers and sending the data ;)
        $host = $this->simulatorURL;
        $port = $this->simulatorPort;
        $timeout = 5; // Timeout in seconds

        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$fp) {
            return false; // If contacting host timeouts or impossible to create the socket, the method returns FALSE
        }

        $headers = [
            "POST / HTTP/1.1",
            "Host: $host",
            "Content-type: text/xml",
            "Content-length: " . strlen($xml),
            "Connection: close"
        ];

        fputs($fp, implode("\r\n", $headers) . "\r\n\r\n");
        fputs($fp, $xml);

        $res = "";
        while (!feof($fp)) {
            $res .= fgets($fp, 128);
        }
        fclose($fp);

        $response = substr($res, strpos($res, "\r\n\r\n") + 4);

        // Now parsing the XML response from RemoteAdmin ;)
        $result = [];
        if (preg_match_all('#<name>(.+)</name><value><(string|int)>(.*)</\2></value>#U', $response, $regs, PREG_SET_ORDER)) {
            foreach ($regs as $val) {
                $result[$val[1]] = $val[3];
            }
        }

        return $result;
    }
}

?>
```

### Key Changes:
1. **Constructor Modernization**: The old-style constructor (`function RemoteAdmin(...)`) is now replaced with `__construct(...)`, which is the standard in all PHP versions starting from PHP 5 and continuing into PHP 8.
  
2. **Type Declarations**: I've added type declarations for class properties and method parameters to enhance code clarity and avoid type-related bugs.

3. **Error Suppression and Handling**: The `@` operator is used to suppress potential warnings when opening the socket connection (`fsockopen`). This prevents unnecessary warnings from being displayed.

4. **XML Special Characters Handling**: Added `ENT_XML1` to `htmlspecialchars()` to ensure proper handling of XML special characters.

5. **Array Processing**: The loop handling parameters has been optimized using `foreach`, which is more readable and efficient.

This updated version should be fully compatible with PHP 8.
