<?php

namespace Xsanisty\UserManager\Controller;

use Exception;
use Xsanisty\Admin\DashboardModule;
use Xsanisty\UserManager\Repository\GroupRepositoryInterface;
use Xsanisty\UserManager\Repository\PermissionRepositoryInterface;
use Xsanisty\UserManager\Repository\UserRepositoryInterface;

class GroupController
{
    protected $groupRepository;
    protected $userRepository;
    protected $permissionRepository;

    public function __construct(
        GroupRepositoryInterface $groupRepository,
        UserRepositoryInterface $userRepository,
        PermissionRepositoryInterface $permissionRepository
    ) {
        $this->groupRepository      = $groupRepository;
        $this->userRepository       = $userRepository;
        $this->permissionRepository = $permissionRepository;
    }

    public function index()
    {
        Event::fire(DashboardModule::INIT);
        Menu::get('admin_sidebar')->setActive('user-manager.manage-group');

        return View::make(
            '@silexstarter-usermanager/group/index',
            [
                'title'         => 'Manage Groups',
                'page_title'    => 'Manage Groups',
                'current_user'  => $this->userRepository->getCurrentUser(),
                'permissions'   => $this->permissionRepository->groupByCategory()
            ]
        );
    }

    public function datatable()
    {
        $currentUser    = $this->userRepository->getCurrentUser();
        $hasEditAccess  = $currentUser ? $currentUser->hasAnyAccess(['admin', 'usermanager.group.edit']) : false;
        $hasDeleteAccess= $currentUser ? $currentUser->hasAnyAccess(['admin', 'usermanager.group.delete']) : false;
        $editTemplate   = '<button href="%s" class="btn btn-xs btn-primary btn-edit" style="margin-right: 5px">edit</button>';
        $deleteTemplate = '<button href="%s" class="btn btn-xs btn-danger btn-delete" style="margin-right: 5px">delete</button>';

        $datatable      = Datatable::of($this->groupRepository->createDatatableQuery())
                        ->setColumn(['name', 'description', 'id'])
                        ->setFormatter(
                            function ($row) use ($hasEditAccess, $hasDeleteAccess, $editTemplate, $deleteTemplate) {
                                $editButton     = $hasEditAccess
                                                ? sprintf($editTemplate, Url::to('usermanager.group.edit', ['id' => $row->id]))
                                                : '';
                                $deleteButton   = $hasDeleteAccess
                                                ? sprintf($deleteTemplate, Url::to('usermanager.group.delete', ['id' => $row->id]))
                                                : '';
                                return [
                                    $row->name,
                                    $row->description,
                                    $editButton.$deleteButton
                                ];
                            }
                        )
                        ->make();

        return Response::json($datatable);
    }

    public function store()
    {
        try {
            $group = Request::get();

            unset($group['_method']);
            unset($group['id']);

            $this->groupRepository->create($group);

            return Response::ajax('Group has been created');
        } catch (Exception $e) {
            return Response::ajax(
                'Error occured while creating group',
                500,
                [
                    'message'   => $e->getMessage(),
                    'code'      => $e->getCode()
                ]
            );
        }
    }

    public function edit($id)
    {
        if (Request::ajax()) {
            return Response::json($this->groupRepository->findById($id));
        }
    }

    public function update($id)
    {
        try {
            $group = Request::get();

            unset($group['_method']);
            unset($group['id']);

            $this->groupRepository->update($id, $group);

            return Response::ajax('Group has been updated');
        } catch (Exception $e) {
            return Response::ajax(
                'Error occured while updating group',
                500,
                [
                    'message'   => $e->getMessage(),
                    'code'      => $e->getCode()
                ]
            );
        }
    }

    public function delete($id)
    {
        try {

            $this->groupRepository->delete($id);

            return Response::ajax('Group has been deleted');
        } catch (Exception $e) {
            return Response::ajax(
                'Error occured while deleting group',
                500,
                [
                    'message'   => $e->getMessage(),
                    'code'      => $e->getCode()
                ]
            );
        }
    }
}
