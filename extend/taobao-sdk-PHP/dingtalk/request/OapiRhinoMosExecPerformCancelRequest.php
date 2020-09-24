<?php
/**
 * dingtalk API: dingtalk.oapi.rhino.mos.exec.perform.cancel request
 * 
 * @author auto create
 * @since 1.0, 2020.06.22
 */
class OapiRhinoMosExecPerformCancelRequest
{
	/** 
	 * 工序执行记录ID列表
	 **/
	private $operationPerformRecordIds;
	
	/** 
	 * 租户ID列表
	 **/
	private $tenantId;
	
	/** 
	 * 系统参数
	 **/
	private $userid;
	
	private $apiParas = array();
	
	public function setOperationPerformRecordIds($operationPerformRecordIds)
	{
		$this->operationPerformRecordIds = $operationPerformRecordIds;
		$this->apiParas["operation_perform_record_ids"] = $operationPerformRecordIds;
	}

	public function getOperationPerformRecordIds()
	{
		return $this->operationPerformRecordIds;
	}

	public function setTenantId($tenantId)
	{
		$this->tenantId = $tenantId;
		$this->apiParas["tenant_id"] = $tenantId;
	}

	public function getTenantId()
	{
		return $this->tenantId;
	}

	public function setUserid($userid)
	{
		$this->userid = $userid;
		$this->apiParas["userid"] = $userid;
	}

	public function getUserid()
	{
		return $this->userid;
	}

	public function getApiMethodName()
	{
		return "dingtalk.oapi.rhino.mos.exec.perform.cancel";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->operationPerformRecordIds,"operationPerformRecordIds");
		RequestCheckUtil::checkMaxListSize($this->operationPerformRecordIds,500,"operationPerformRecordIds");
		RequestCheckUtil::checkNotNull($this->tenantId,"tenantId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}