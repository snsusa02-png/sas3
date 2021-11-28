<!--#include virtual="/inc/CreateDbConnection.asp"-->
<!--#include file="../access.asp"-->
<!--#include file="updlog.asp"-->
<!--#include virtual="/inc/isDebug.asp"-->
<!--#include virtual="/inc/IntFolder.asp"-->
<!--#include virtual="/inc/NoCache.asp"-->

<!--#include virtual ="/inc/PageACLByID.asp"-->
<!--#include virtual ="/inc/PageTitleByID.asp"-->
<!--#include virtual ="/inc/UpdPageUsage.asp"-->

<%
PageID	= "898"

response.expires=0
Response.AddHeader "pragma", "no-cache"

cIntFolder = IntFolder()
SysID	= trim(request.querystring("sysid"))
SID		= ucase(trim(request.querystring("sid")))
ACSID	= trim(request.querystring("ACSID"))
vStaffID= trim(request.querystring("StfID"))
if SysID = "" and SID = "" and ACSID = "" then response.redirect cIntFolder

ttt = UpdPageUsage( PageID, session("StaffID"))





RetURL	= trim(request.querystring("ref"))
if RetURL = "" then
	RetURL	= trim(request.querystring("returl"))
end if

'Alpha=ucase(trim(request.querystring("a")))
vRR=ucase(trim(request.querystring("rr")))
vRA=ucase(trim(request.querystring("ra")))
thisScript=Request.ServerVariables("SCRIPT_NAME")

set Conn = CreateDbConnection("pelican_11", "sns")

if SID<>"" or SysID<>"" or ACSID <> "" then
	if SysID<>"" then
		SQL="select nID, ID, Code, Name,Description, StartPageID from SoftSystems where nID='"&SysID&"'"
	elseif SID <> "" then
		SQL="select nID, ID, Code, Name,Description, StartPageID from SoftSystems where Code='"&SID&"'"
	
	elseif ACSID <> "" then	
		SQL="select '' nID, ID, Code, Name, '' Description, '' StartPageID from sns.ACS t where t.ID='"&ACSID&"'"
	end if
	'response.write "<br>"&SQL
	Set RS=Conn.Execute(SQL)
	if not rs.eof then
	    nSysID=trim(rs("nID"))					'Идентификатор системы (новый)
	    SysID=trim(rs("ID"))					'Идентификатор системы
	    SID=trim(rs("Code"))					'Код-Идентификатор системы
	    SysName=trim(rs("Name"))				'Наименование системы
	    SysDescription=trim(rs("Description"))	'Описание системы
	    SysStartPageID=trim(rs("StartPageID"))	'ID Начальной страницы системы
		
	end if
end if

Referer = ""
Referer = RetURL
if Referer = "" then 
	if SysStartPageID <> "" then Referer = cIntFolder & "/page.asp?id=" & SysStartPageID
end if	


  'response.write "<br>"&SID&"Rights="&Session(SID&"RIGHTS")
if Session(SID&"RIGHTS")="" then
	'response.end
	if not (Access(SID) or isDebug(12) or isDebug(14) or isDebug(81) or isDebug(74)) and Referer <> "" then 
		response.redirect (Referer)
	end if
end if
if instr(Session(SID&"RIGHTS"),"A")=0 then
  ttt=updLog("Попытка несанкц. доступа к ACL "&SID,updatedTbl,ID)
  'response.redirect(Referer)
end if
ttt=updLog("Предоставлен доступ к ACL "&SID,updatedTbl,ID)

'if trim(request.form("Action"))="1" then
if Request.ServerVariables("REQUEST_METHOD") = "POST" then
	vSysFuncID	= trim(request.form("vSysFuncID"))
	vStfName	= trim(request.form("vStfName"))
	vRR=trim(request.form("vRR"))
	vRC=trim(request.form("vRC"))
	vRW=trim(request.form("vRW"))
	vRD=trim(request.form("vRD"))
	vRA=trim(request.form("vRA"))
	vRole=trim(request.form("vRole"))
	
	session("vSysFuncID"&nSysID) = vSysFuncID
	session("vStfName"&nSysID) = vStfName
else
	if vSysFuncID="" then vSysFuncID=trim(request.querystring("vSysFuncID"))
	if vRR="" then vRR=trim(request.querystring("vRR"))
	if vRC="" then vRC=trim(request.querystring("vRC"))
	if vRW="" then vRW=trim(request.querystring("vRW"))
	if vRD="" then vRD=trim(request.querystring("vRD"))
	if vRA="" then vRA=trim(request.querystring("vRA"))
	if vRole="" then vRole=trim(request.querystring("vRole"))

	Alpha = ucase(trim(request.querystring("a")))
	if Alpha = "" then
		Alpha = session("ACL_Alpha")
	else
		if Alpha = "*" then Alpha = ""
		session("ACL_Alpha") = Alpha
	end if	


end if

vSysFuncID	= session("vSysFuncID"&nSysID)
vStfName	= session("vStfName"&nSysID)

cQs="&vrr="&vRR&"&vrc="&vRC&"&vrw="&vRW&"&vrd="&vRD&"&vra="&vRA
%>

<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
	<meta name="viewport" content="width=device-width, initial-scale=1">
		
	<link rel="stylesheet" href="/inc/bootstrap/4.0.0/css/bootstrap.min.css">

	<link rel="stylesheet" type="text/css" href="app.css">
	<style>
	.slct_A{
		background-color: #FFFF99;
		text-decoration: none;
		font-weight: bold;
		font-size: 16px;
		padding-left: 2px;
		padding-right: 2px;
	}
	</style>
	<link rel="stylesheet" type="text/css" href="/inc/jquery/bootstrap_popover/bootstrap.css">
	
</head>

<body bgcolor="#E2E2E2">
	
	<nav class="navbar navbar-expand-md bg-light navbar-light">
		<a class="navbar-brand" href="#">ACL: <b><%=SysName%></b> &nbsp;(<%=SID%>) </a>
		
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
			<span class="navbar-toggler-icon"></span>
		</button>
	  
		<div class="collapse navbar-collapse" id="collapsibleNavbar">
			<ul class="navbar-nav">

				<%
				if Referer <> "" then
					%>
					<li class="nav-item">
						<a class="nav-link" href="<%=referer%>"><b>Назад</b></a>
					</li>    
					<%
				end if
				%>
				<li class="nav-item">
					<a class="nav-link" href="clrrights.asp?sid=<%=SID%>">ClrRights</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="clrSessions.asp?sid=<%=SID%>">ClrSessions</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="../page.asp?id=1">ДСП</a>
				</li>    
			</ul>
		</div>  
	</nav>
	
	
	
	<div class="container">
	

<!--<div align="right">
<%
if Referer <> "" then
	%>| <a href="<%=referer%>">Назад</a><%
end if%>		
| <a href="clrrights.asp?sid=<%=SID%>">ClrRights</a>
| <a href="clrSessions.asp?sid=<%=SID%>">ClrSessions</a>
| <a href="../page.asp?id=1">ДСП</a>
|</div>
-->


<form action="" method="post" name="form" id="form">
	<input type="hidden" name="action" value="1">
	<input type="hidden" id="SysID" value="<%=nSysID%>">
  
  <table border="1" cellspacing="0" cellpadding="3" align="center" bgcolor="#FFFFFF" class="table fnt12">
  <tr><td colspan="9" style="background-color: #3366FF; color: #F1F1F1; font-family: 'MS Sans Serif', Geneva, sans-serif; font-size: 14px;">
  &nbsp;Управление контролем доступа к системе:
  &nbsp; <b><%=SysName%></b> &nbsp;(<%=SID%>) <a href="/internal/admin/i/edtSoftSystem.asp?nid=<%=nSysID%>">...</a>
  <%if SysDescription<>"" then%><br><font class="fnt10"><%=SysDescription%></font><%end if%>&nbsp;</td></tr>

<tr><td colspan="9" bgcolor="#EEEEEE">

	<table width="100%" border="0" cellspacing="2" cellpadding="0">
		<tr style="font-family: 'MS Sans Serif', Geneva, sans-serif; font-size: 9px;">
			<td><%
				sql = "select substr(os.LName, 1, 1) a, count(*) cnt"_
					& "  from sns.OrgStaff os"_
					& " where os.ID in (select staffid"_
					& "                   from sns.Stfsysrights sr"_
					& "                  where sr.sysfuncid in"_
					& "                        (select id from sftSysFuncs sf where sf.sysid ='"& nSysID &"')"_
						& "             and sysdate between nvl(sr.begdt, sysdate) and nvl(sr.enddt, sysdate))"_
					& "group by substr(os.LName, 1, 1)"_
					& "order by 1"
				'response.write "<br>"&SQL
				Set RS=Conn.Execute(SQL)
				do while not rs.eof
					A = trim(rs("A"))
					shwA = A
					if A = Alpha then shwA = "<span class=""slct_A"">"& A & "</span>"
					response.write " <a href="&thisScript&"?sid="&sid&"&a="&A&cQS&">" & shwA &"</a>"
					rs.movenext
				loop
					%> <a href="<%=thisscript%>?sid=<%=sid%><%=cQS%>&a=*">-все-</a>
					&nbsp; <a href="<%=thisscript%>?sid=<%=sid%><%=cQS%>&stfID=<%=session("StaffID")%>">-я-</a>
				</td>
				<td align="right">&nbsp;<a href="edtAcs.asp?sid=<%=nSysID%>&sc=<%=sid%>&dflt=1">права по-умолчанию</a></td>
			</tr>
		</table>
	</td>
</tr>

  <tr bgcolor="#9D9D9D">
  <th bgcolor="#FFFFDD">ФИО, должность</th>
  <th bgcolor="#FFFFDD">Кол-во прав</th>
  <th bgcolor="#FFFFFF"><a href="addp2acl.asp?sid=<%=nSysID%>&sc=<%=sid%>&acsid=<%=ACSID%>"><img border="0" alt="" src="/images/signs/ico_add.gif"></a></th>
  </tr>


  <tr bgcolor="#9D9D9D">
	  <td align="center">
	  	<input type="text" name="vStfName" value="<%=vStfName%>" style="font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 11px;"  class="form-control">
	  </td>
	  <td align="center"><%
		sql = "select id, name || decode(Right_Code, null, null, ' [' || sf.Right_Code || ']') Name"_
			& " from sns.SftSysFuncs sf where SysID = '"& nSysID & "'"
		sql = sql & " order by Right_Code, name"
		'response.write "<br>"&sql
		%><select name="vSysFuncID" style="font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 10px;" class="form-control">
		<option value="">-</option><%
		Set Rs = Conn.Execute(sql)
		Do while not rs.eof 
			tID		= trim(rs("ID"))
			tName	= trim(rs("Name"))
			%><option value="<%=tID%>"<%if vSysFuncID=tID then response.write " SELECTED"%>><%=tName%></option><%
			rs.moveNext
		loop

		%>
		</select>
		</th>
		
		<th><button type="submit" name="Action" value="Отобрать"  class="form-control">Отобрать</button></th>
  </tr>
</form> 
<%

sql = "select OrgID, o.Name OrgName, count(*) FuncCnt"_
	& "  from sns.stfSysRights sr"_
	& " left join sns.Orgs o on o.ID=sr.OrgID"_
	& " where sr.sysfuncid in (select id from sns.Sftsysfuncs sf where sf.sysid = '"& nSysID &"')"_
	& "   and nvl(sr.endDT, sysdate) >= sysdate"_
	& "   and sr.StaffID is null"_
	& " group by OrgID, o.Name"
     
'response.write "<br>"&SQL
Set RS=Conn.Execute(SQL)
if not rs.eof then
	n = 0
	Do While Not RS.EOF

		n = n+1
		FuncCnt = trim(rs("FuncCnt"))
		OrgID	= trim(rs("OrgID"))
		OrgName	= trim(rs("OrgName"))

		FuncCnt = trim(rs("FuncCnt"))
		StaffID=""
		UserFN="- профиль доступа &laquo;по-умолчанию&raquo; -"
		Post="для пользователей, не имеющих персональных прав"

		%><tr bgcolor="#EAFEE2"><td>&nbsp;<b><%=UserFN%></b>
		<br>&nbsp;&nbsp;&nbsp;<font class="fnt10"><%=Post%>
		<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em><b><%=OrgName%></b></em></font></td>
		<td align="center" bgcolor="#FFFFDD"><%=FuncCnt%></td>
		<td align="center"><a href="edtAcs.asp?sid=<%=nSysID%>&sc=<%=sid%>&dflt=1&orgid=<%=OrgID%>" title="изменить"><img src="/images/signs/edit.gif" border=0></a></td>
		</tr><%
		rs.movenext
	loop	
end if

sql = "select p.StaffID ID, p.StaffID, p.Cnt FuncCnt,"_
	& "       decode(p.StaffID,"_
	& "              null,"_
	& "              '-по умолчанию-',"_
	& "              os.lname || ' ' || os.fname || ' ' || nvl(MName, ' ')) UserFN,"_
	& "       os.active,"_
	& "       o.Name OrgName,"_
	& "       op.Name as Post"_
	& "  from (select StaffID, count(*) cnt"_
	& "          from sns.stfsysrights sr"_
	& "         where nvl(sr.EndDT, sysdate) >= sysdate"_
	& " and sr.StaffID is not null"_
    & "	          and sr.sysfuncid in"_
	& "               (select id from sns.Sftsysfuncs sf where sf.sysid = '"&nSysID&"'"

if vSysFuncID<>"" then
	'20131023 SNS. Ограничение по обладателям заданного права
	sql = sql & " and sr.SysFuncID = '"&vSysFuncID&"'"
end if
	
sql = sql & ")"_
	& "           and sysdate between nvl(sr.begdt, sysdate) and nvl(sr.enddt, sysdate)"_
	& "         group by StaffID) p"_
	& "  /*left*/ join sns.OrgStaff os"_
	& "    on os.id = p.StaffID"_
	& "  left join sns.OrgPosts op"_
	& "    on op.id = os.PostID"_
	& "  left join sns.Orgs o on o.ID = os.OrgID"_
	& " where 1=1 and staffid is not null"

if vStfName<>"" then
	'20141027 SNS. отбор по ФИО
	sql = sql & " and lower(' ' || os.LName || ' ' || os.FName )  like  '% " & lcase(vStfName) & "%'"
end if

if Alpha<>"" then
	SQL=SQL+" and LName like '"&Alpha&"%'"
end if
if vRR<>"" then
	SQL=SQL+" and Rights like '%R%'"
end if
if vRC<>"" then
	SQL=SQL+" and Rights like '%C%'"
end if
if vRW<>"" then
	SQL=SQL+" and Rights like '%W%'"
end if
if vRD<>"" then
	SQL=SQL+" and Rights like '%D%'"
end if
if vRA<>"" then
	SQL=SQL+" and Rights like '%A%'"
end if
if vRole<>"" then
	SQL=SQL+" and Roles like '%"&vRole&"%'"
end if
if vStaffID<>"" then
	SQL=SQL+" and os.ID = '"&vStaffID&"'"
end if
SQL=SQL+" order by UserFN"

if isDebug(71) then response.write "<br>"&SQL
'response.end

Set RS=Conn.Execute(SQL)
if not rs.eof then
	n = 0
  Do While Not RS.EOF

	n = n+1
	ID		= trim(rs("ID"))
    StaffID	= trim(rs("StaffID"))
	FuncCnt = trim(rs("FuncCnt"))
	UserFN	= trim(rs("UserFN"))
	OrgName	= trim(rs("OrgName"))
    Post	= trim(rs("Post"))
	Active	= trim(rs("Active"))

	bgcolor="#FFfFfF"
	if Active="0" then bgcolor="#FFBFBF"
    %><tr bgcolor="<%=bgcolor%>"><td><%=n%>.&nbsp;<b><%=UserFN%></b>
	<br>&nbsp;&nbsp;&nbsp;<font class="fnt10"><%=Post%>
	<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em><%=OrgName%></em></font>
	</td>
    <td align="center" bgcolor="#FFFFDD"><a class="ViewRights" data="<%=StaffID%>" style="cursor: pointer;"><%=FuncCnt%></a></td>
    <td align="center"><a href="edtAcs.asp?sid=<%=nSysID%>&id=<%=id%>" title="изменить"><img src="/images/signs/edit.gif" border=0></a></td>
    </tr>
    <%
    rs.movenext
  loop
end if
response.write "</table>"
Conn.close
set Conn=nothing
%>
	| <a href="<%=referer%>">Назад</a>
	|
	<%
	'Полезные материалы---------------------------------
	txt_Ref_Tbl = "SNS.PAGES"
	txt_Ref_NID = PageID
	%>
	<!-- #include virtual ="/internal/texts/inc_LnkdTexts.asp"-->	
	<%
	' end Полезные материалы----------------------------
	%>

	</div>

	<script type="text/javascript" src="/inc/jquery/jquery-1.10.js"></script>
	<script type="text/javascript" src="/inc/jquery/bootstrap-3/dist/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="Acslst.js"></script>
</body>

</html>
