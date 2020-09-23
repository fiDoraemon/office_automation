<?php
/**
 * dingtalk API: dingtalk.oapi.rhino.mos.exec.clothes.finish request
 * 
 * @author auto create
 * @since 1.0, 2020.04.16
 */
class OapiRhinoMosExecClothesFinishRequest
{
	/** 
	 * 衣服ID列表
	 **/
	private $entityIds;
	
	/** 
	 * 租户ID
	 **/
	private $tenantId;
	
	/** 
	 * 业务参数[这里先预留],这里是用户ID,比如钉钉用户ID
	 **/
	private $userid;
	
	private $apiParas = array();
	
	public function setEntityIds($entityIds)
	{
		$this->entityIds = $entityIds;
		$this->apiParas["entity_ids"] = $entityIds;
	}

	public function getEntityIds()
	{
		return $this->entityIds;
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
		return "dingtalk.oapi.rhino.mos.exec.clothes.finish";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->entityIds,"entityIds");
		RequestCheckUtil::checkMaxListSize($this->entityIds,500,"entityIds");
		RequestCheckUtil::checkNotNull($this->tenantId,"tenantId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
