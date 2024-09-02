<?php
/*********************************************************************** 
Copyright (c) 2008, The New World Grid Regents http://www.newworldgrid.com and Contributors. Version: 2024 PHP8+
All rights reserved.
 
Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met: 
	* Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
	* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer 
	in the documentation and/or other materials provided with the distribution.
	* Neither the name of the New World Grid nor the names of its contributors may be used to endorse or promote products derived 
	from this software without specific prior written permission.
 
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, 
THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, 
OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. 
***********************************************************************/
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
