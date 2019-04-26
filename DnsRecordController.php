<?php
/**
 * Created by: Sanija K
 * User : vinam
 * Date : 4-04-2019 09:30 AM
 */
namespace App\Controller;

use App\Constants\CommonConstants;
use App\Controller\MainDnsController;
use App\Entity\DnsGroup;
use App\Entity\DnsHistory;
use App\Entity\DnsRecord;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\RecheckRecordService;
use \DateTime;

class DnsRecordController extends MainDnsController
{

    /**
     * @param $session SessionInterface
     */
    public function __construct(SessionInterface $session)
    {
        parent::__construct($session);
    }

    /**
     * @Route("/")
     * @Route("/home/{grpId}", name="home")
     * @param $request Request
     * @param $paginator PaginatorInterface
     */

    public function manageDns($grpId = 0, Request $request, PaginatorInterface $paginator)
    {
        $this->setPublicValues($request);
        $userid = $this->userId;
        $repository = $this->getDoctrine()->getRepository(DnsGroup::class);
        $records = $repository->findByStatus($userid);
        $result = $paginator->paginate(
            $records,
            $request->query->getInt('page', 1),
            5
        );
        $id = array();
        $data = $result->getItems();
        foreach ($data as $key => $value) {
            $id[] = $value['id'];
        }
        $rep = $this->getDoctrine()->getRepository(DnsRecord::class);
        $res = $rep->findCount($id);
        return $this->render('dns.html.twig', [
            'loggedinuser' => $this->userName,
            'result' => $result,
            'res' => $res,
            'grpId' => $grpId,
        ]);
    }

    /**
     * @Route("/insertRecord", name="insertRecord")
     * @param $request Request
     * @return  JsonResponse
     */
    public function insertRecord(Request $request): JsonResponse
    {
        $response = [];
        try {
            $this->setPublicValues($request);
            $entityManager = $this->getDoctrine()->getManager();
            $formdata = $request->request->all();
            $obj = new DnsRecord();
            $formdata['dateCreated'] = new DateTime();
            $formdata['dateModified'] = new DateTime();
            $formdata['userid'] = $this->userId;
            $formdata['priority'] = 1;
            $formdata['status'] = 1;
            $formdata['result'] = '';
            $chkDuplicate = $entityManager->getRepository(DnsRecord::class)->findOneBy(['name' => $formdata['name'], 'value' => $formdata['value'], 'status' => 1]);
            if ($chkDuplicate == null) {
                $dnsGroup = $entityManager->getRepository(DnsGroup::class)->insertData($formdata, $obj);
                $response = ['status' => 'Success'];
            } else {
                $response = ['status' => 'Failure'];
            }
        } catch (Exception $e) {
            $response = ['status' => 'Failure'];
        }
        return new JsonResponse($response);
    }

    /**
     * @Route("/insertdns", name="insertdns")
     * @param $request Request
     */
    public function insertDns(Request $request)
    {
        $this->setPublicValues($request);
        $entityManager = $this->getDoctrine()->getManager();
        $formdata = $request->request->all();
        $obj = new DnsGroup();
        if (isset($_POST['save'])) {
            $formdata['datecreated'] = new DateTime();
            $formdata['datemodified'] = new DateTime();
            $formdata['userid'] = $this->userId;
            $formdata['status'] = 1;
            $formdata['name'] = trim($formdata['name']);
            $chkDuplicate = $entityManager->getRepository(DnsGroup::class)->findOneBy(['name' => $formdata['name'], 'status' => $formdata['status']]);
            if ($chkDuplicate == null) {
                $dnsGroup = $entityManager->getRepository(DnsGroup::class)->insertData($formdata, $obj);
            }
            return $this->redirectToRoute('home');
        }
    }

    /**
     * @Route("/deleteGroup", name="deleteGroup")
     * @param $request Request
     * @return  JsonResponse
     */

    public function deleteEntry(Request $request): JsonResponse
    {
        $response = [];
        $id = $request->request->get('id');
        $em = $this->getDoctrine()->getManager();
        $chkEntry = $em->getRepository(DnsGroup::class)->findOneBy(['id' => $id, 'status' => 1]);
        if ($chkEntry != null) {
            $entry = $em->getRepository(DnsGroup::class)->updateGrpStatus($id);
            $response = ['status' => 'success'];
        } else {
            $response = ['status' => 'failure'];
        }
        return new JsonResponse($response);
    }

    /**
     * @Route("/recordChanged", name="recordChanged")
     * @param $request Request
     * @return  JsonResponse
     */
    public function recordChanged(Request $request): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();
        $formdata = $request->request->all();
        $formdata['dateModified'] = new DateTime();
        $entry = $entityManager->getRepository(DnsRecord::class)->updateRecord($formdata['id'], $formdata['value'], $formdata['type'], $formdata['dateModified']);
        $response = ['status' => 'success'];
        return new JsonResponse($response);
    }

    /**
     * @Route("/recheckRecord", name="recheckRecord")
     * @param $request Request
     * @param $recheckRecord RecheckRecordService
     * @return  JsonResponse
     */

    public function recheckRecord(Request $request,RecheckRecordService $recheckRecord): JsonResponse
    {
        $returnValue = 0;
        $response = [];
        $recheckData = $request->request->all();
        $em = $this->getDoctrine()->getManager();
        $record = $em->getRepository(DnsRecord::class)->findOneBy(['id' => $recheckData['id']]);
        $data = $record->toArray();
        $obj = new DnsHistory();
        if ($recheckData['type'] == 'A') {
            $returnValue = $recheckRecord->getDNSRecord($data);
        } else if ($recheckData['type'] == 'MX') {
            $returnValue = $recheckRecord->getDNSRecord($data);
        } else if ($recheckData['type'] == 'PTR') {
            $returnValue = $recheckRecord->getDNSRecord($data);
        } else if ($recheckData['type'] == 'TXT') {
            $returnValue = $recheckRecord->getDNSRecord($data);
        } else {
            $returnValue = $recheckRecord->getDNSRecord($data);
        }
        if ($returnValue) {
            $data['dnsRecordId'] = $data['id'];
            $data['result'] = CommonConstants::dnsSuccess;
            $data['dateTime'] = new DateTime();
            $result = $em->getRepository(DnsGroup::class)->insertData($data, $obj);
            $res = $em->getRepository(DnsRecord::class)->updateData($data['result'], $data['dnsRecordId']);
            $response = ['status' => 'verified','check' => true];
            return new JsonResponse($response);
        } else {
            $data['dnsRecordId'] = $data['id'];
            $data['result'] = CommonConstants::dnsfailure;
            $data['dateTime'] = new DateTime();
            $result = $em->getRepository(DnsGroup::class)->insertData($data, $obj);
            $res = $em->getRepository(DnsRecord::class)->updateData($data['result'], $data['dnsRecordId']);
            $response = ['status' => 'not verified','check' => false];
            return new JsonResponse($response);
        }
    }

    /**
     * @Route("/view", name="entryView")
     * @param $request Request
     * @return  JsonResponse
     */
    public function viewEntry(Request $request): JsonResponse
    {
        $this->setPublicValues($request);
        $id = $request->request->get('id');
        $em = $this->getDoctrine()->getManager();
        $entry = $em->getRepository(DnsRecord::class)->findBy(['dnsGroupId' => $id, 'status' => 1]);
        $data = array();
        foreach ($entry as $key => $value) {
            $value = $value->toArray();
            if ($value['type'] == 'A') {
                $data['A'][] = $value;
            } else if ($value['type'] == 'TXT') {
                $data['TXT'][] = $value;
            } else if ($value['type'] == 'MX') {
                $data['MX'][] = $value;
            } else if ($value['type'] == 'CNAME') {
                $data['CNAME'][] = $value;
            } else {
                $data['PTR'][] = $value;
            }
        }
        return new JsonResponse($data);
    }

    /**
     * @Route("/deleteMultipleRecord", name="deleteMultipleRecord")
     * @param $request Request
     * @return  JsonResponse
     */
    public function deleteMultipleRecordEntry(Request $request): JsonResponse
    {
        $this->setPublicValues($request);
        $id = $request->request->get('id');
        $em = $this->getDoctrine()->getManager();
        foreach ($id as $ids) {
            $entry = $em->getRepository(DnsRecord::class)->updateStatus($ids);
        }
        $response = ['status' => 'success', 'data' => $entry];
        return new JsonResponse($response);
    }

    /**
     * @Route("/recordHistory", name="recordHistory")
     * @param $request Request
     * @return  JsonResponse
     */
    public function recordHistory(Request $request): JsonResponse
    {
        $this->setPublicValues($request);
        $id = $request->request->get('id');
        $em = $this->getDoctrine()->getManager();
        $entry = $em->getRepository(DnsHistory::class)->findRecordHistory($id);
        return new JsonResponse($entry);
    }

    /**
     * @Route("/deleteRecord", name="deleteRecord")
     * @param $request Request
     * @return  JsonResponse
     */
    public function deleteRecordEntry(Request $request): JsonResponse
    {
        $this->setPublicValues($request);
        $id = $request->request->get('id');
        $em = $this->getDoctrine()->getManager();
        $chkEntry = $em->getRepository(DnsRecord::class)->findOneBy(['id' => $id]);
        if ($chkEntry != null) {
            $entry = $em->getRepository(DnsRecord::class)->updateStatus($id);
            $response = ['status' => 'success'];
        } else {
            $response = ['status' => 'failure'];
        }
        return new JsonResponse($response);
    }
}
