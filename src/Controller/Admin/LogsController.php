<?php
namespace Controller\Admin;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use Exception;
use Repository\LogRecordRepository;

/**
 * @IsGranted({"SITE_ADMIN_2FA"})
 * @Route("/admin/logs", name="admin_logs_")
 */
class LogsController extends Controller
{
    /**
     * @Route(name="index")
     *
     * @param Request             $request
     * @param LogRecordRepository $logRecordRepository
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(Request $request, LogRecordRepository $logRecordRepository): Response
    {
        $limit       = 50;
        $search      = $request->query->get('search');
        $searchLevel = $request->query->get('level');
        $page        = $request->query->getInt('page', 1);
        $offset      = ($page - 1) * $limit;

        if ($search && $searchLevel) {
            $logs  = $logRecordRepository->findByMessageAndLevel($search, $searchLevel, $limit, $offset);
            $total = $logRecordRepository->findFoundRows();
        } else if ($search) {
            $logs  = $logRecordRepository->findByMessage($search, $limit, $offset);
            $total = $logRecordRepository->findFoundRows();
        } else if ($searchLevel) {
            $logs  = $logRecordRepository->findByLevel($searchLevel, $limit, $offset);
            $total = $logRecordRepository->findFoundRows();
        } else {
            $logs  = $logRecordRepository->findAll($limit, $offset);
            $all   = $logRecordRepository->findAll();
            $total = count($all);
        }

        return $this->render('admin/logs/index.html.twig', [
            'logs'        => $logs,
            'search'      => $search,
            'searchLevel' => $searchLevel,
            'totalPages'  => ceil($total / $limit),
            'total'       => $total,
            'page'        => $page
        ]);
    }

    /**
     * @Route("/{id}", name="view")
     *
     * @param int                 $id
     * @param LogRecordRepository $logRecordRepository
     *
     * @return Response
     * @throws Exception
     */
    public function viewAction(int $id, LogRecordRepository $logRecordRepository): Response
    {
        $logRecord = $logRecordRepository->findByID($id);
        if (!$logRecord) {
            $this->throwNotFound();
        }

        return $this->render('admin/logs/view.html.twig', [
            'logRecord' => $logRecord
        ]);
    }
}
