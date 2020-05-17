{literal}
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/data.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
{/literal}

<!-- User Info -->
<div class="panel panel-default">

	<div class="panel-heading">Information</div>

	<div class="panel-body">

	<div class ="col-sm-12">

	<div id="getdetail" style="display:none;">

	</div>

		<div class="row">

			<div class ="col-sm-12">

				<div class="text-center">

					<i id="loader" class="fa"></i>

					<span class="sr-only">Loading...</span>

				</div>

			</div>

		</div>

			<div class ="col-sm-8">

				<div class="form-group row">

					<label class="col-sm-3 col-form-label">User Id :</label>

					<div class="col-sm-3">

					<span id="userid"> </span>

					</div>

				</div>

			</div>

			<div class ="col-sm-8">

				<div class="form-group row ">

					<label class="col-sm-3 col-form-label">Display Name :</label>

					<div class="col-sm-5">

					<span id="displayname"> </span>

					</div>	

				</div>	

			</div>

			<div class ="col-sm-8">

				<div class="form-group row">

					<label class="col-sm-3 col-form-label">Email :</label>

					<div class="col-sm-5">

					<span id="email"> </span>

					</div>	

				</div>	

			</div>

			

			<div class ="col-sm-8">

				<div class="form-group row">

					<label class="col-sm-3 col-form-label">User Quota<br>(Size | object) :</label><br>

					<div class="col-sm-5">

						<span id="userQuotasize"></span> <span>|</span> <span id="userQuotaobject"></span>

					</div>	

				</div>	

			</div>	

		</div>

		<div>  

			<button class="btn btn-warning"   data-toggle="modal"   data-target="#updatemodal">Edit</button>

			{*<button class="btn btn-primary"  data-toggle="modal"   data-target="#subusermodal1" >Add SubUsers</button>*}

			<button class="btn btn-primary"  data-toggle="modal"   data-target="#bucketmodal1" onclick="reset();">Add Bucket </button>

			<button class="btn btn-success"   onclick="Getkeys(this);">Get keys</button>

			{*<button class="btn btn-success"   onclick="Getbucketinfo();">Bucket list</button>*}
	 
		</div><br>

		<div id="getkeys" style="display:none;">

			<div class ="col-sm-12">

					<div class="form-group row ">

							<label class ="col-sm-4">User Name :</label>

						<div class="col-sm-8">

							<span id="username"> </span>

						</div>	

					</div>

					<div class="form-group row ">

							<label class ="col-sm-4">Access Key:</label>

						<div class="col-sm-8">

							<span id="accesskey"> </span>

						</div>	

					</div>

					<div class="form-group row ">

							<label class ="col-sm-4" >Secret Key:</label>

						<div class="col-sm-8">

							<span id="secretkey"></span>

						</div>	

					</div>

			</div>	

		</div>

	</div>

	</div>

<!-- bucket list -->
<div class="panel panel-default">

	<div class="panel-heading">Bucket List</div>

		<div class="panel-body">

			<div class ="col-sm-12">

				<div class="form-group row" >

					 <table id ="Bucketlist" class="table  table-bordered table-striped table-hover  " border="0" width="100%">

						<thead>

							<tr class="text-primary">

								<th>Name</th>

							{*	<th>Placement Rule</th>

								<th>Owner</th> *}

								<th>Bucket Quota(Size)</th>

								<th>Object</th>

								<th>Action</th>

							</tr>

						</thead>

						<tbody></tbody>

					</table>	

				</div>	

			</div>

		</div>

	</div> 

</div>
 
 
<!-- data usage graph -->

<div class="panel panel-default"  id="datastats" style="display:none;">

	<div class="panel-heading">Data usage Statistics</div>

		<div class="panel-body">

			<div class ="col-sm-12">

				<div class="form-group row" >
					<div id="stats" class="text-center"></div> 
				     
				 <div id="usage">

				 </div>
               
			   
				</div>	

			</div>

		</div>

	</div> 

 
	

 

<!-- Update modal -->

<div class="modal fade" id="updatemodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">

	<div class="modal-dialog" role="document">

		<div class="modal-content">

	 

		<div class="modal-header">         

			<button type="button" class="close" data-dismiss="modal" aria-label="Close">

			  <span aria-hidden="true">&times;</span>

			</button>

			<h4 class="modal-title">Update User</h4>

		</div> 

		<div class="modal-body">

			<div >

				<div class="text-center">

					<div id="update_resp"></div>

				</div>

				<form method="post" id="update_form" action=" " >	

					<div class="form-group">

						<label class="text-info" ><b>User ID:</b></label>

						<input type="text" placeholder=" " id="userid1" name="userid" class="form-control">

					</div>

					<div class="form-group">

						<label class="text-info" ><b>Display Name:</b></label>

						<input type="text" placeholder=" " id="displayname1" name="displayname" class="form-control">

					</div>

					 <div class="form-group">

						<label class="text-info" ><b>Email</b></label>

						<input type="text" placeholder="" id="email1" name="email" class="form-control" >

					</div>		

				</form>	

			</div>

			<div class="modal-footer">

				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

				<button type="button" class="btn btn-warning"   onclick="UpdateInfo(this, 'update');">Update</button>

			</div>

		</div>

	</div>

</div>

</div>



<!-- Bucket Quota  modal -->

<div class="modal fade" id="bucketmodal1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">

	<div class="modal-dialog" role="document">

		<div class="modal-content">

	 

		<div class="modal-header">         

			<button type="button" class="close" data-dismiss="modal" aria-label="Close">

			  <span aria-hidden="true">&times;</span>

			</button>

			<h4 class="modal-title">Add Bucket</h4>

		</div> 

		<div class="modal-body">

			<div>

				<div class="text-center">

					<div id="bucket_resp"></div>

			</div>

			<form method="post" id="bucket_form" action=" " >
				 <button type="reset" id="reset" style="display: none;"> </button>
					<div class="form-group">

						<label class="text-info" ><b>Name:</b></label>

						<input type="text" placeholder=" " id="name" name="name" class="form-control">

					</div>

					<div class="form-group" style="display: none;">

						<label class="text-info" ><b>Placement rule:</b></label>

						<select name="Placementrule" >
 							 <option   value="{$placement_rule}">{$placement_rule} </option>
						</select>
						{*<input type="radio" name="Placementrule" value="defaultplacement"  checked>Default Placement 

						<input type="text" placeholder=" " id="placementrule" name="placementrule" class="form-control">*}

					</div>

					{*<div class="form-group">

						<label class="text-info" ><b>Owner:</b></label>

						<input type="radio" name="defaultuid" value=" "  checked>Auto Fill Owner

						<input type="text" placeholder=" " id="owner" name="owner" class="form-control">

					</div>*}

					

					 

					<div class="form-group ">

						<label class="text-info">permissions:</label>

						<select name="permission" id="permission">

							<option value="FullControl">Full Control</option>

							<option value="Read">Read</option>

							<option value="Write">Write</option>

							<option value="ReadAcp">Read Acp</option>

							<option value="WriteAcp">Write Acp</option>

						</select>

						 

					</div>

					<div class="form-group">

						<input type="checkbox" name="enable" value="enable" id="myCheck" onclick="check_box();"> Enable Quota Size 

						<div id="text" style="display:none">

							<label class="text-info">Add Bucket Quota:</label>

							<input type="number"  name="addbucketquota" id="Bucketquota"  class="form-control">

							<select name="unit" id="units">

								<option value="mb" >MB</option>

								<option value="gb" >GB</option>

								<option value="tb" >TB</option>

							</select>

							<label class="text-info">Add Bucket Object:</label>

							<input type="number" placeholder=" " id="Bucketobject" name="Bucketobject" class="form-control">							
						</div>	
					</div>		
			</form>
		</div>

			<div class="modal-footer">

				  
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" onclick="BucketAdd(this,'AddBucket');">submit</button>
				{*<input type="submit" class="btn btn-primary" >*}
			</div>

		</div>

	</div>

 </div>

 

 </div>





{*<!-- subuser  modal -->

<div class="modal fade" id="subusermodal1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">

	<div class="modal-dialog" role="document">

		<div class="modal-content">

	 

		<div class="modal-header">         

			<button type="button" class="close" data-dismiss="modal" aria-label="Close">

			  <span aria-hidden="true">&times;</span>

			</button>

			<h4 class="modal-title">New SubUser</h4>

		</div> 

		<div class="modal-body">

			<div class ="col-sm-12">

				<div class="text-center">

					<div id="addsubuser_resp"></div>

				</div>

				<form method="post" id="SubUser_form" action=" " >

			<div class=" row col-sm-12"><label>permissions</label><br>

				<div class="form-group col-sm-6">

					<input type="radio" name="permission" value="read">  read        

				</div>

				<div class="form-group col-sm-6">

					<input type="radio" name="permission" value="write">  write<br>   

				</div>

				<div class=" row col-sm-12">

					<div class="form-group col-sm-6">

						  <input type="radio" name="permission" value="readwrite">  read | write<br>   

					</div>

					<div class="form-group col-sm-6">

						<input type="radio" name="permission" value="fullcontrol">  Full control        

					</div>

				</div>

				<div class="form-group">	

					<label class="text-info" ><b>New SubUsers ID:</b></label>

					<input type="text" placeholder=" " id="addsubuser" name="addsubuser" class="form-control">

				</div>

			</div>  	

		</form>

			</div>

			<div class="modal-footer">

				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

				<button type="button" class="btn btn-warning"   onclick="AddSubUser(this, 'subuser');">Update</button>

			</div>

		</div>

	</div>

</div>

</div>*}

<!-- Bucket Add only  modal -->

<div class="modal fade" id="buckuctadd2" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">

	<div class="modal-dialog" role="document">

		<div class="modal-content">

		<div class="modal-header">         

			<button type="button" class="close" data-dismiss="modal" aria-label="Close">

				<span aria-hidden="true">&times;</span>

			</button>

				<h4 class="modal-title">Add Quota</h4>

		</div> 

			<div class="modal-body">

				<div class="text-center">

					<div id="bucket_resp1"></div>

				</div>
				<div class="text-center">

					<div id="bucket_spiner"></div>

				</div>

				<form method="post" id="Quota_form_only" action=" " >
					
					 
					<input type="hidden" name="bucketname" value="" id="bucket"  >

					<label class="text-info">Add Bucket Quota:</label>

						<div class="form-group">

							<input type="number"  name="addbucketquota1" id="Bucketquotaonly"  class="form-control"  >

								<select name="unit" id="unitsonly">
									<option value="kb" >KB</option>
									<option value="mb" >MB</option>

									<option value="gb" >GB</option>

									<option value="tb" >TB</option>

								</select>

						</div>	

							<label class="text-info">Add Bucket Object:</label>

							<input type="number" placeholder=" " id="Bucketobjectonly" name="Bucketobject1" class="form-control">

				</form>

			</div>

			<div class="modal-footer">

				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

				<button type="button" class="btn btn-warning"   onclick="addbucketdata(this,'AddBucketonly');">submit</button>

			</div>

		</div>

	</div>
 
 
<script type="text/javascript" src="{$WEB_ROOT}/modules/servers/croit/js/client.js"></script>
<link rel="stylesheet" type="text/css" href="{$WEB_ROOT}/modules/servers/croit/style/style.css" />
 