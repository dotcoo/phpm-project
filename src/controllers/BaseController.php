<?php

namespace app\controllers;

use Closure;
use net\phpm\framework\Controller;
use net\phpm\framework\Sql;
use net\phpm\framework\Request;
use net\phpm\framework\Response;

abstract class BaseController extends Controller {

  protected string $modelClass = '';

  protected function indexQuery(Request $req, Response $res, string $rowVerify = '', string $searchVerify = '', array $wheres = [], array $keywordColumns = [], array $defaultOrders = []) : Sql {
    $params = dataVerify($req->json, 'columns列名A[s],rows编号A[m],ids编号A[i],keyword关键字S,orders排序A[s],pageNum页码I(1),pageSize大小I(20)');
    ['columns' => $columns, 'rows' => $rows, 'ids' => $ids, 'keyword' => $keyword, 'orders' => $orders, 'pageNum' => $pageNum, 'pageSize' => $pageSize] = $params;
    if (empty($orders)) {
      $orders = $defaultOrders;
    }
    $query = $this->modelClass::columns(...$columns)->page($pageNum, $pageSize);
    if (!empty($rows)) {
      $query->whereOr(...arraylist($rows)->map(fn($v) => whereByAssoc(!empty($rowVerify) ? dataVerify($v, $rowVerify) : $v)->toWhere()));
    }
    if (!empty($ids)) {
      $query->where('`id` in (?)', $ids);
    }
    $json = !empty($searchVerify) ? dataVerify($req->json, $searchVerify) : array_diff_key($req->json, $params);
    if (!empty($wheres)) {
      foreach ($wheres as $where) {
        for ($l = count($where), $i = 1; $i < $l; $i++) {
          $name = $where[$i];
          $value = is_string($name) ? (array_key_exists($name, $req->json) && $req->json[$name] !== '' && $req->json[$name] !== null ? $json[$name] : null) : $name($json, $req->json);
          if ($value === null) { continue 2; }
          $where[$i] = $value;
        }
        $query->where(...$where);
      }
    } else {
      $query->whereByAssoc($json);
    }
    if (!empty($keywordColumns) && !empty($keyword)) {
      $where = [[]];
      foreach ($keywordColumns as $column) {
        $where[0][] = "`$column` LIKE ?";
        $where[] = "%{$keyword}%";
      }
      $where[0] = '(' . implode(' OR ', $where[0]) . ')';
      $query->where(...$where);
    }
    if (!empty($orders)) {
      foreach ($orders as $column) {
        $query->order("`" . strtolower($column) . "` " . (ctype_upper($column) ? 'DESC' : 'ASC'));
      }
    }
    return $query;
  }

  protected function indexHandle(Request $req, Response $res, string $rowVerify = '', string $searchVerify = '', array $wheres = [], array $keywordColumns = [], array $orders = []) : void {
    $query = $this->indexQuery($req, $res, $rowVerify, $searchVerify, $wheres, $keywordColumns, $orders);
    $list = $query->selectAll();
    $total = $query->count();
    $res->success('获取成功!', compact('list', 'total'));
  }

  protected string $indexRowVerify = '';
  protected string $indexSearchVerify = '';
  protected array $indexWheres = [];
  protected array $indexKeywordColumns = [];
  protected array $indexOrders = [];

  public function index(Request $req, Response $res) : void {
    $this->indexHandle($req, $res, $this->indexRowVerify, $this->indexSearchVerify, $this->indexWheres, $this->indexKeywordColumns, $this->indexOrders);
  }

  protected function detailHandle(Request $req, Response $res, string $verify = '') : void {
    $row = empty($verify) ? $req->json : dataVerify($req->json, $verify);
    if (empty($row)) { $res->error('查询条件不能为空!'); }
    $detail = $this->modelClass::whereByAssoc($row)->select();
    if (!$detail) { $res->error('数据不存在!'); }
    $res->success('获取成功!', compact('detail'));
  }

  protected string $detailVerify = '';

  public function detail(Request $req, Response $res) : void {
    $this->detailHandle($req, $res, $this->detailVerify);
  }

  protected function addHandle(Request $req, Response $res, string $verify = '') : void {
    $rows = array_is_list($req->json) ? $req->json : [$req->json];
    foreach ($rows as $row) {
      $detail = $this->modelClass::new(empty($verify) ? $row : dataVerify($row, $verify));
      $detail->add();
    }
    $res->success('添加成功!', ['data' => $detail->getIds()]);
  }

  protected string $addVerify = '';

  public function add(Request $req, Response $res) : void {
    $this->addHandle($req, $res, $this->addVerify);
  }

  protected function editHandle(Request $req, Response $res, string $verify = '') : void {
    $rows = array_is_list($req->json) ? $req->json : [$req->json];
    foreach ($rows as $row) {
      $detail = $this->modelClass::new(empty($verify) ? $row : dataVerify($row, $verify));
      $detail->edit();
    }
    $res->success('修改成功!', ['data' => $detail->getIds()]);
  }

  protected string $editVerify = '';

  public function edit(Request $req, Response $res) : void {
    $this->editHandle($req, $res, $this->editVerify);
  }

  protected function delHandle(Request $req, Response $res, string $verify = '') : void {
    $params = dataVerify($req->json, 'columns列名A[s],rows编号A[m],ids编号A[i],keyword关键字S,orders排序A[s],pageNum页码I(1),pageSize大小I(20)');
    ['ids' => $ids] = $params;
    if (array_is_list($req->json)) {
      $this->modelClass::whereOr(...arraylist($req->json)->map(fn($v) => whereByAssoc(!empty($verify) ? dataVerify($v, $verify) : $v)->toWhere()))->delete();
    } else if (!empty($ids)) {
      $this->modelClass::where('`id` in (?)', $ids)->delete();
    } else {
      $this->modelClass::whereByAssoc(!empty($verify) ? dataVerify($req->json, $verify) : $req->json)->delete();
    }
    $res->success('删除成功!');
  }

  protected string $delVerify = '';

  public function del(Request $req, Response $res) : void {
    $this->delHandle($req, $res, $this->delVerify);
  }

  public function status(Request $req, Response $res) : void {
    $this->edit($req, $res, 'id编号i,status状态u');
  }

  public function ord(Request $req, Response $res) : void {
    $this->edit($req, $res, 'id编号i,ord排序u');
  }
}
