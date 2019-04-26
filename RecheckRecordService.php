<?php
namespace App\Service;

use Metaregistrar\DNS\dnsProtocol;
 
class RecheckRecordService
{
    private $dns;
    
    /**
     * RecheckRecordService constructor.
     * @param dnsProtocol $dns
     */
    public function __construct()
    {
        $this->dns = new dnsProtocol();
        $this->dns->setServer('208.67.222.222');
    }
    
    /**
     * @param array $data
     * @return bool
     */
    public function getDNSRecord($data): bool
    {
        $flag=false;
        if (!in_array($data['type'], ['A', 'MX', 'CNAME', 'PTR', 'TXT', 'NS'])) {
            return false;
        }
        $result = $this->dns->Query($data['name'], $data['type']);
        foreach ($result->getResourceResults() as $resource) {
            $typeClass = "\\Metaregistrar\\DNS\\dns{$data['type']}result";
            if ($resource instanceof $typeClass) {
                if($data['type'] == 'TXT'){  
                    if(trim($data['value']) == substr($resource->getRecord(), 1))
                    { 
                        $flag = true;
                    } 
                }
                else if($data['type'] == 'A')
                {
                    if(trim($data['value']) == $resource->getIpv4()){ 
                        $flag = true;
                    }
                }
               else if($data['type'] == 'MX')
               {
                   if(trim($data['value']) == $resource->getServer()){ 
                       $flag=true;
                   }
               }
               else if($data['type'] == 'CNAME')
               {
                   if(trim($data['value']) == $resource->getRedirect()){ 
                       $flag = true;
                   }
               }
               else if($data['type'] == 'PTR'){
                   if(trim($data['value']) == $resource->getData()){ 
                       $flag = true;
                   }
               }
               else{
                   $flag = false;
               }    
            }
        }
        return $flag;     
    }
}
?>
