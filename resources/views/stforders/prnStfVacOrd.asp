<%@LANGUAGE="VBSCRIPT" CODEPAGE="1251"%>

<%
'20100920 SNS Данные по сотрудникам, подписавшим приказ будем брать из таблицы SNS.StfOrd_Signers

'20170405 SNS. Изменил вызов OrgFullName на OrgFullNameByID, так как последняя учитывает, что название организации могло измениться, и возвращает его на указанную дату.

%>

<!--#include virtual="/inc/decodestr.asp"-->
<!--#include virtual="/inc/IsStfWorkNow.asp"-->
<!--#include virtual="/inc/OrgFullNameByID.asp"-->
<!--#include file="getstfmngid.asp"-->
<!--#include file="../dbopen.asp"-->
<!--#include virtual="/inc/codeStr.asp"-->
<!--#include virtual="/inc/isDebug.asp"-->
<%

thisTitle="Печать приказа"
thisScript=Request.ServerVariables("SCRIPT_NAME")

ID=trim(request.QueryString("ID"))
if ID<>"" then

	'Read data from Table -----------------------------------
	SQL="select * from STFORDERS where ID='"&ID&"'"
	Set RS=Conn.Execute(SQL)
	if not rs.eof then
		StaffID=trim(rs("StaffID"))
		ORDTYPE=ucase(trim(rs("ORDTYPE")))
		OrdNum=trim(rs("OrdNum"))
		OrdDate=trim(rs("OrdDate"))
		BEGDATE=trim(rs("BEGDATE"))
		ENDDATE=trim(rs("ENDDATE"))
		DEPID=trim(rs("DEPID"))
		POSTID=trim(rs("POSTID"))
		SALARY=trim(rs("SALARY"))
		Reason=trim(rs("Reason"))

'		Signer1ID=trim(rs("Signer1ID"))
'		Signer2ID=trim(rs("Signer2ID"))
'		Signer3ID=trim(rs("Signer3ID"))
'		Signer4ID=trim(rs("Signer4ID"))

		WHOCRT=trim(rs("WHOCRT"))
		WHNCRT=trim(rs("WHNCRT"))
		WHOCHNG=trim(rs("WHOCHNG"))
		WHNCHNG=trim(rs("WHNCHNG"))
		EDOCID=trim(rs("EDOCID"))

		if isNull(OrdDate) or OrdDate=" " then OrdDate="-"
		if isNull(EndDate) or EndDate=" " then EndDate="&nbsp;"
'		if isNull(Signer1ID) then Signer1ID=""
'		if isNull(Signer2ID) then Signer2ID=""
'		if isNull(Signer3ID) then Signer3ID=""
'		if isNull(Signer4ID) then Signer4ID=""


		'OrgID="0000000001"
		'OrgName="ОАО &quot;Приморское агентство авиационных компаний&quot;"

		SQL="select OrgID from orgStaff where ID='"&StaffID&"'"
		Set RS=Conn.Execute(SQL)
		if not rs.eof then
			OrgID=trim(rs("OrgID"))
			
		
			' if isDebug(12) then
			' response.write "<br>"& OrgID
			' response.write "<br>"& OrdDate
			' end if
			
			'20170405 SNS. Выясним полное название организации по состоянию на дату приказа
			OrgName = OrgFullNameByID(OrgID, OrdDate)
			
			b2recOrgName=(inStr(OrgName,"«")>0)
			if b2recOrgName then
				OrgName_p1 = left(OrgName, inStr(OrgName,"«")-1)
				OrgName_p2 = right(OrgName, len(OrgName)-inStr(OrgName,"«")+1)
			end if
		end if 'not rs.eof 

		select case OrdType
		case "VACATION"
			SQL="select * from OrdVacation where id='"&ID&"'"
			'response.write "<br>"&SQL
			Set RS=Conn.Execute(SQL)
			if not rs.eof then
				VacType=trim(rs("VacType"))
				WrkBegDate=trim(rs("WrkBegDate"))
				WrkEndDate=trim(rs("WrkEndDate"))
				PriVacDays=trim(rs("PriVacDays"))
				PriHolDays=trim(rs("PriHolDays"))
				PriBegDate=trim(rs("PriBegDate"))
				PriEndDate=trim(rs("PriEndDate"))
				SecVacType=trim(rs("SecVacType"))
				SecVacDays=trim(rs("SecVacDays"))
				SecHolDays=trim(rs("SecHolDays"))
				SecBegDate=trim(rs("SecBegDate"))
				SecEndDate=trim(rs("SecEndDate"))
				item_3=trim(rs("item_3"))

				if isNull(PriHolDays) then PriHolDays="0"
				if isNull(SecHolDays) then SecHolDays="0"
				if isNull(SecVacType) then SecVacType=""

				if not isNull(WrkBegDate) then
					DateStr=trim(FormatDateTime(WrkBegDate,1))
					wrkBegDay=right("0"&trim(mid(DateStr,1,2)),2)
					wrkBegMnth=trim(mid(DateStr,3,inStr(mid(DateStr,4)," ")))
					wrkBegYear=cstr(year(cdate(WrkBegDate)))
				end if

				if not isNull(WrkEndDate) then
					DateStr=trim(FormatDateTime(WrkEndDate,1))
					wrkEndDay=right("0"&trim(mid(DateStr,1,2)),2)
					wrkEndMnth=trim(mid(DateStr,3,inStr(mid(DateStr,4)," ")))
					wrkEndYear=cstr(year(cdate(WrkEndDate)))
				end if

				if not isNull(PriBegDate) then
					PriBegDate=cdate(PriBegDate)
					PriBegDay=right("0"&day(PriBegDate),2)
					DateStr=trim(FormatDateTime(PriBegDate,1))
					PriBegMnth=trim(mid(DateStr,3,inStr(mid(DateStr,4)," ")))
					PriBegYear=cstr(year(PriBegDate))
				end if

				if not isNull(PriEndDate) then
					PriEndDate=cdate(PriEndDate)
					PriEndDay=right("0"&day(PriEndDate),2)
					DateStr=trim(FormatDateTime(PriEndDate,1))
					PriEndMnth=trim(mid(DateStr,3,inStr(mid(DateStr,4)," ")))
					PriEndYear=cstr(year(PriEndDate))
				end if

				if cint(SecVacDays)>0 then
					if not isNull(SecBegDate) then
						SecBegDate=cdate(SecBegDate)
						SecBegDay=right("0"&day(SecBegDate),2)
						DateStr=trim(FormatDateTime(SecBegDate,1))
						SecBegMnth=trim(mid(DateStr,3,inStr(mid(DateStr,4)," ")))
						SecBegYear=cstr(year(SecBegDate))
					end if

					if not isNull(SecEndDate) then
						SecEndDate=cdate(SecEndDate)
						SecEndDay=right("0"&day(SecEndDate),2)
						DateStr=trim(FormatDateTime(SecEndDate,1))
						SecEndMnth=trim(mid(DateStr,3,inStr(mid(DateStr,4)," ")))
						SecEndYear=cstr(year(SecEndDate))
					end if

					if SecVacDays<>"0" then
						SecVacName="Ежегодный дополнительный оплачиваемый отпуск"
						if SecVacType<>"" then SecVacName=SecVacType
					end if
				end if

				AllVacDays=cint(PriVacDays)+cint(SecVacDays)

				BegDate=cdate(BegDate)
				BegDay=right("0"&day(BegDate),2)
				DateStr=trim(FormatDateTime(BegDate,1))
				BegMnth=trim(mid(DateStr,3,inStr(mid(DateStr,4)," ")))
				BegYear=cstr(year(BegDate))

				EndDate=cdate(EndDate)
				EndDay=right("0"&day(EndDate),2)
				DateStr=trim(FormatDateTime(EndDate,1))
				EndMnth=trim(mid(DateStr,3,inStr(mid(DateStr,4)," ")))
				EndYear=cstr(year(EndDate))
			end if
		end select

		if StaffID<>"" then
			SQL="select OrgID,DepID,PostID, LName,FName,MName, TabNum, AltDepName from orgStaff where id='"&StaffID&"'"
			'response.write "<br>"&SQL
			Set RS=Conn.Execute(SQL)
			if not rs.eof then
				OrgID=trim(rs("OrgID"))
				PostID=trim(rs("PostID"))
				PrsnFIO=uCase(trim(rs("LName")))&" "&trim(rs("FName"))&" "&trim(rs("MName"))
				TabNum=trim(rs("TabNum"))
				AltDepName=trim(rs("AltDepName"))
			end if

		end if

		if PostID<>""  then
			SQL="select Name,DepID from OrgPosts where ID='"+PostID+"'"
			Set RS=Conn.Execute(SQL)
			if not rs.eof then
				PostName=decodestr(rs("Name"))
				PostName=ucase(left(PostName,1)) &  mid(PostName,2)
				DepID=trim(rs("DepID"))
			end if
		end if

		if DepID<>"" then
			SQL="select Name from Deps where ID='"+DepID+"'"
			Set RS=Conn.Execute(SQL)
			if not rs.eof then
				DepName=decodestr(rs("Name"))
			end if
		end if

		if AltDepName<>"" then DepName=DepName + " / "+ AltDepName

		'20100920 SNS берем подписи из sns.StfOrd_Signers
		%><!--#include file="_defStfOrdSgnrs.asp"--><%

	else
		response.write "<br>Запись не найдена!"
		response.write "<br><a href="&retURL&">вернуться назад</a>"
		response.end
	end if
	'end of Read data from Table ----------------------------
end if
%>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
	<title><%=thisTitle%></title>
<link rel=stylesheet type="text/css" href="/internal/css/app.css">
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<script Language="JavaScript">
function IsPrinting(){
   if(navigator.appName.indexOf('Netscape')>=0){
	if(navigator.appVersion.substr(0,3)>=4.0) return true;
   }
   if(navigator.appName.indexOf('Microsoft')>=0){
	if(navigator.appVersion.indexOf('MSIE 5.')>=0 ||
		navigator.appVersion.indexOf('MSIE 6.')>=0) return true;
   }
   return false;
}

function Print(){
  if ( !factory.object ) {
    alert("factory.object не сработал!");
	}else {
	factory.printing.portrait = true;
	factory.printing.leftMargin = 10;
	factory.printing.topMargin = 5;
	factory.printing.rightMargin = 5;
	factory.printing.bottomMargin = 5;
	factory.printing.header = "";
	factory.printing.footer = "";
	}

	if(IsPrinting()){
		//if(confirm('Печатать документ?')){
			window.print();
			setTimeout("PrintDone()",33000);
		//}
	}else{
		alert('Чтобы распечатать документ выберете в меню File->Print (Файл->Печать)');
	}
}
function PrintDone(){
	//alert('Печать завершена?');
	window.close();
}
</SCRIPT>
<style type="text/css">
.brd1blck_ltrb_bold {
	border-left: 2px solid Black;
	border-Top: 2px solid Black;
	border-right: 2px solid Black;
	border-Bottom: 2px solid Black;
}
</style>
<style type="text/css">
<!--

table1 {  font-family: "Courier New", Courier, mono; font-size: 12px; font-style: normal; border-top-width: 0px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 40px}
table {
	border: White;
	font-family: "Times New Roman", Times, serif;
	font-size: 12px;
	font-style: normal;
	border-top-width: 0px;
	border-right-width: 0px;
	border-bottom-width: 0px;
	border-left-width: 60px;
}
.t0l0r0b1 {  border-color: black black #000000; border-style: solid; border-top-width: 0px; border-right-width: 0px; border-bottom-width: 1px; border-left-width: 0px}
.t0l1r1b1 {  border: #000000; border-style: solid; border-top-width: 0px; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 1px}
.t1l1r1b1 {  border: #000000; border-style: solid; border-top-width: 1px; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 1px}
.t1l0r1b1 {  border-color: #000000 #000000 #000000 black; border-style: solid; border-top-width: 1px; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 0px}
.t1l1r0b1 {  border: #000000; border-style: solid; border-top-width: 1px; border-right-width: 0px; border-bottom-width: 1px; border-left-width: 1px}
.t0l1r0b1 {  border: #000000; border-style: solid; border-top-width: 0px; border-right-width: 0px; border-bottom-width: 1px; border-left-width: 1px}
.t1l1r0b0 {  border-color: #000000 black black #000000; border-style: solid; border-top-width: 1px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 1px}
.t0l1r0b0 {  border-color: black black black #000000; border-style: solid; border-top-width: 0px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 1px}
.t1l1r1b0 {  border: #000000; border-style: solid; border-top-width: 1px; border-right-width: 1px; border-bottom-width: 0px; border-left-width: 1px}
.t0l0r0b11 {border-color: black black #000000; border-style: solid; border-top-width: 0px; border-right-width: 0px; border-bottom-width: 1px; border-left-width: 0px}
.t0l1r0b11 {border: #000000; border-style: solid; border-top-width: 0px; border-right-width: 0px; border-bottom-width: 1px; border-left-width: 1px}
.t0l1r1b11 {border: #000000; border-style: solid; border-top-width: 0px; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 1px}
.t1l1r0b11 {border: #000000; border-style: solid; border-top-width: 1px; border-right-width: 0px; border-bottom-width: 1px; border-left-width: 1px}
.t1l1r1b11 {border: #000000; border-style: solid; border-top-width: 1px; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 1px}
-->
</style>
</head>

<body bgcolor="#FFFFff" onLoad="Print();">
<!-- MeadCo ScriptX -->
<object id=factory style="display:none"
  classid="clsid:1663ed61-23eb-11d2-b92f-008048fdd814"
  codebase="http://192.168.1.231/_tools/ScriptX.cab#Version=6,1,432,1">
</object>

<table width="700" border="0" cellspacing="0" cellpadding="0" align="center" bgcolor="#FFFFFF">
<tr><td style="width: 1cm;">&nbsp;</td><td>

<table border="0" cellspacing="0" cellpadding="0" align="center" width="100%" bgcolor="#FFFFFF">
  <tr>
    <td><table border="0" cellspacing="0" cellpadding="0" align="right" bgcolor="#FFFFFF">
      <tr>
        <td style="font-size: 10;">Унифицированная форма № Т-6<br>
          Утверждена постановлением Госкомстата России<br>
          от 05.01.2004 № 1</td>
      </tr>
    </table></td>
  </tr>

  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="68%">&nbsp;</td>
        <td width="10%">&nbsp;</td>
        <td width="7%">&nbsp;</td>
        <td width="1%">&nbsp;</td>
        <td width="14%" align="center" class="t1l1r1b11">Код</td>
      </tr>
      <tr>
        <td width="68%">&nbsp;</td>
        <td colspan="2" align="right">Форма по ОКУД</td>
        <td width="1%">&nbsp;</td>
        <td width="14%" align="center" class="t0l1r1b11">0301005</td>
      </tr>
      <tr>
        <td colspan="2" align="center"></td>
        <td width="7%">&nbsp;</td>
        <td width="1%">&nbsp;</td>
        <td rowspan="2" width="14%" class="t0l1r1b11">&nbsp;</td>
      </tr>
<%if not b2recOrgName then%>
      <tr>
        <td colspan="2" align="center" class="t0l0r0b11"><b><%=OrgName%></b></td>
        <td width="7%" align="right" nowrap="nowrap">&nbsp;&nbsp;по ОКПО</td>
        <td width="1%">&nbsp;</td>
      </tr>
<%else%>
      <tr>
        <td colspan="2" align="left" class="t0l0r0b11">
			<b style="font-size: 16px;">&nbsp;&nbsp;<%=OrgName_p1%></b></td>
        <td width="7%" align="right" nowrap="nowrap">&nbsp;&nbsp;по ОКПО</td>
        <td width="1%">&nbsp;</td>
      </tr>
      <tr>
        <td colspan="2" align="left" class="t0l0r0b11">
			<b style="font-size: 16px;">&nbsp;&nbsp;<%=OrgName_p2%></b></td>
        <td width="7%" align="right" nowrap="nowrap">&nbsp;&nbsp;</td>
        <td width="1%">&nbsp;</td>
      </tr>
<%end if%>
      <tr>
        <td colspan="2" align="center" valign="top" class="fnt8"><sup>(наименование организации)</sup></td>
        <td width="7%">&nbsp;</td>
        <td width="1%">&nbsp;</td>
        <td width="14%">&nbsp;</td>
      </tr>

    </table></td>
  </tr>
  <tr>
    <td>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr nowrap>
    <td rowspan="2" width="210">&nbsp;</td>
    <td rowspan="2" align="center" valign="bottom"><b style="font-size: 16px;">ПРИКАЗ</b></td>
    <td width="106" class="t1l1r0b11" align="center" nowrap>&nbsp;Номер документа&nbsp;</td>
    <td width="105" class="t1l1r1b11" align="center">&nbsp;Дата составления&nbsp;</td>
  </tr>
  <tr nowrap>
    <td class="t0l1r0b11" align="center"><b style="font-size: 16px;"><%=OrdNum%></b></td>
    <td class="t0l1r1b11" align="center"><b style="font-size: 16px;"><%=OrdDate%></b></td>
  </tr>
  <tr><td colspan="6" align="center"><b>(распоряжение)<br>
          о предоставлении отпуска работнику</b></td></tr>
</table>
</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="86%">1. Предоставить отпуск</td>
        <td rowspan="2" align="center" valign="top" class="t1l1r1b11">Табельный 
          номер</td>
      </tr>
      <tr>
        <td width="86%">&nbsp;</td>
      </tr>
      <tr>
        <td width="86%" align="center" class="t0l0r0b11"><b style="font-size: 16;"><%=PrsnFIO%></b></td>
        <td width="14%" align="center" class="t0l1r1b11"><b><%=TabNum%></b>&nbsp;</td>
      </tr>
      <tr>
        <td width="86%" valign="top" align="center" class="fnt8"><sup>(фамилия, имя, отчество)</sup></td>
        <td width="14%">&nbsp;</td>
      </tr>
    </table></td>
  </tr><%'response.End()%>
  <tr>
    <td align="center" class="t0l0r0b11"><b style="font-size: 16px;"><%=DepName%></b>
        <div class="fnt8"><sup>(структурное подразделение)</sup></div>
      <b style="font-size: 16px;"><%=PostName%></b>
        <div class="fnt8"><sup>должность (специальность, профессия)</sup></div></td>
  </tr>
  <tr>
    <td><br>
        <table border="0" cellspacing="0" cellpadding="0">
          <tr style="font-size: 14px;">
            <td>За период работы с &laquo;</td>
            <th class="t0l0r0b11" width="30" align="center"><%=WrkBegDay%></th>
            <td>&raquo; </td>
            <th width="50" align="center" class="t0l0r0b11"><%=WrkBegMnth%></th>
            <td>&nbsp;</td>
            <th width="30" align="center" class="t0l0r0b11"><%=WrkBegYear%></th>
            <td>г. &nbsp;по &laquo;</td>
            <th width="30" align="center" class="t0l0r0b11"><%=wrkEndDay%></th>
            <td >&raquo; &nbsp;</td>
            <th width="50" align="center" class="t0l0r0b11"><%=wrkEndMnth%></th>
            <td>&nbsp;</td>
            <th width="30" align="center" class="t0l0r0b11"><%=wrkEndYear%></th>
            <td width="14">г.</td>
          </tr>
      </table></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td><br>
        <table border="0" cellspacing="0" cellpadding="0">
          <tr valign="middle">
            <td valign="top"><b>1.А.</b></td>
            <td valign="top" nowrap width="170">&nbsp;Ежегодный оплачиваемый&nbsp;<br>
              отпуск на&nbsp;</td>
            <td height="32" width="150" align="center" class="t1l1r1b11"><b><font style="font-size: 14px;"><%=PriVacDays%></font></b></td>
            <td nowrap>&nbsp;&nbsp;календарных дней</td>
          </tr>
      </table></td>
  </tr>
  <tr>
    <td><br>
        <table border="0" cellspacing="0" cellpadding="0">
          <tr style="font-size: 14px;">
            <td>с &laquo;</td>
            <th class="t0l0r0b11" width="30" align="center"><%=PriBegDay%></th>
            <td>&raquo;&nbsp; </td>
            <th width="50" align="center" class="t0l0r0b11"><%=PriBegMnth%></th>
            <td>&nbsp;</td>
            <th width="30" align="center" class="t0l0r0b11"><%=PriBegYear%></th>
            <td>г. &nbsp;&nbsp;по &laquo;</td>
            <th width="30" align="center" class="t0l0r0b11"><%=PriEndDay%></th>
            <td >&raquo; </td>
            <th width="50" align="center" class="t0l0r0b11"><%=PriEndMnth%></th>
            <td>&nbsp;</td>
            <th width="30" align="center" class="t0l0r0b11"><%=PriEndYear%></th>
            <td width="14">г.</td>
          </tr>
      </table></td>
  </tr>
  <tr>
    <td><br>
      и (или)<br></td>
  </tr>
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="3%"><b>Б.</b></td>
        <td width="97%" align="left" class="t0l0r0b11">&nbsp;<%=SecVacName%></td>
      </tr>
      <tr>
        <td width="3%">&nbsp;</td>
        <td width="97%" align="center" valign="top" class="fnt8"><sup>(ежегодный дополнительный оплачиваемый 
          отпуск, учебный, без сохранения заработной платы и другие (указать))</sup></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td><table border="0" cellspacing="0" cellpadding="0" width="43%">
      <tr>
        <td valign="top">На</td>
        <td width="150" height="32" align="center" class="t1l1r1b11"><b><font style="font-size: 14px;"><%=SecVacDays%></font></b></td>
        <td width="29%" align="right" nowrap>&nbsp;&nbsp;календарных дней</td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td><br>
        <table border="0" cellspacing="0" cellpadding="0">
          <tr style="font-size: 14px;">
            <td>с &laquo;</td>
            <th class="t0l0r0b11" width="30" align="center"><%=SecBegDay%></th>
            <td>&raquo;</td>
            <th width="50" align="center" class="t0l0r0b11"><%=SecBegMnth%></th>
            <td>&nbsp;</td>
            <th width="30" align="center" class="t0l0r0b11"><%=SecBegYear%></th>
            <td>г. &nbsp;по &laquo;</td>
            <th width="30" align="center" class="t0l0r0b11"><%=SecEndDay%></th>
            <td >&raquo; </td>
            <th width="50" align="center" class="t0l0r0b11"><%=SecEndMnth%></th>
            <td>&nbsp;</td>
            <th width="30" align="center" class="t0l0r0b11"><%=SecEndYear%></th>
            <td width="14">г.</td>
          </tr>
      </table></td>
  </tr>
  <tr>
    <td><br>
        <table border="0" cellspacing="0" cellpadding="0" width="43%">
          <tr>
            <td valign="top"><b>В.</b></td>
            <td valign="top">&nbsp;Всего отпуск на&nbsp;</td>
            <td height="32" width="150" align="center" class="t1l1r1b11"><b><font style="font-size: 14px;"><%=AllVacDays%></font></b></td>
            <td align="right" nowrap>&nbsp;&nbsp;календарных дней</td>
          </tr>
      </table></td>
  </tr>
  <tr>
    <td><br>
        <table border="0" cellspacing="0" cellpadding="0">
          <tr style="font-size: 14px;">
            <td>с &laquo;</td>
            <th class="t0l0r0b11" width="30" align="center"><b><%=BegDay%></b></th>
            <td>&raquo;</td>
            <th width="50" align="center" class="t0l0r0b11"><b><%=BegMnth%></b></th>
            <td>&nbsp;</td>
            <th width="30" align="center" class="t0l0r0b11"><b><%=BegYear%></b></th>
            <td>г. &nbsp;по &laquo;</td>
            <th width="30" align="center" class="t0l0r0b11"><b><%=EndDay%></b></th>
            <td >&raquo;</td>
            <th width="50" align="center" class="t0l0r0b11"><b><%=EndMnth%></b></th>
            <td>&nbsp;</td>
            <th width="30" align="center" class="t0l0r0b11"><b><%=EndYear%></b></th>
            <td width="14">г.</td>
          </tr>
      </table></td>
  </tr>
<%if not isNull(item_3) and item_3<>Empty then%>
  <tr><td><br>
	<div><b><%=codeStr(item_3)%></b></div>
  </td></tr>  
<%end if%>
  <tr>
    <td>
        <table width="100%" border="0" cellspacing="0" cellpadding="2" >
          <tr >
            <td valign="bottom" width="72" height="35" align="left" >Основание:</td>
            <td valign="bottom" class="brd1blck_b">&nbsp;<b><%=Reason%></b>&nbsp;</td>
          </tr>
      </table></td>
  </tr>
  <tr>
    <td><br>
        <table width="100%" border="0" cellspacing="0" cellpadding="2">
          <tr valign="middle" >
            <td ><b>Руководитель организации</b></td>
            <td align="center" valign="bottom" class="brd1blck_b"><b style="font-size: 14px;"><%=Signer1Post%></b></td>
            <td>&nbsp;</td>
            <td align="center" valign="bottom" class="brd1blck_b"><b>&nbsp;</b></td>
            <td>&nbsp;</td>
            <td align="center" valign="bottom" class="brd1blck_b"><b style="font-size: 14px;"><%=Signer1FIO%></b></td>
          </tr>
          <tr>
            <td align="center"></td>
            <td align="center" class="fnt8"><sup>(должность)</sup></td>
            <td>&nbsp;</td>
            <td align="center" class="fnt8"><sup>(личная подпись)</sup></td>
            <td>&nbsp;</td>
            <td align="center" class="fnt8"><sup>(расшифровка подписи)</sup>
      </table></td>
  </tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
  <tr> 
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td><br>
      <table border="0" cellspacing="0" cellpadding="0">
        <tr valign="bottom">
          <td width="315"><b>С приказом (распоряжением) работник ознакомлен</b></td>
          <td width="100" class="t0l0r0b1">&nbsp;</td>
          <td>&nbsp;&nbsp;&laquo;</td>
          <td width="30" class="t0l0r0b1">&nbsp;</td>
          <td>&raquo;</td>
          <td width="80" class="t0l0r0b1">&nbsp;</td>
          <td>20</td>
          <td width="30" class="t0l0r0b1">&nbsp;</td>
          <td>г.</td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td align="center" valign="top" class="fnt8"><sup>(личная подпись)</sup></td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
      </table>
    </td>
  </tr>
  <tr><td>&nbsp;</td></tr>
  <tr> 
    <td align="left">

<%if Signer2ID<>"" or Signer3ID<>"" or Signer4ID<>"" then%>
     <table border="0" cellspacing="0" cellpadding="3">
        <tr><td colspan="3">Согласовано:</td></tr>
	<%if Signer2ID<>"" then%>
        <tr valign="middle" > 
          <td align="right" valign="bottom"><%=Signer2Post%></td>
          <td width="100" align="center" valign="bottom" class="brd1blck_b"><b>&nbsp;</b></td>
          <td valign="bottom" ><%=Signer2FIO%></td>
        </tr>
	<%end if 'Signer2ID<>""

	if Signer3ID<>"" then%>
        <tr valign="middle"> 
          <td align="right" valign="bottom"><%=Signer3Post%></td>
          <td width="100" align="center" valign="bottom" class="brd1blck_b"><b>&nbsp;</b></td>
          <td valign="bottom" ><%=Signer3FIO%></td>
        </tr>
	<%end if 'Signer3ID<>""

	if Signer4ID<>"" then%>
        <tr valign="middle">
          <td align="right" valign="bottom"><%=Signer4Post%></td>
          <td width="100" align="center" valign="bottom" class="brd1blck_b"><b>&nbsp;</b></td>
          <td valign="bottom" ><%=Signer4FIO%></td>
        </tr>
	<%end if 'Signer4ID<>""%>
      </table>
<%end if 'Signer2ID<>"" or Signer3ID<>"" or Signer4ID<>""%>

    </td>
  </tr>
</table>

    </td>
  </tr>
</table>

</td></tr></table>
</body></html>
<!--#include file="../dbclose.asp"-->
