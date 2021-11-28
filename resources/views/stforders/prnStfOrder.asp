<%@LANGUAGE="VBSCRIPT" CODEPAGE="1251"%>

<%
'110401 SNS в связи с тем, что записи в Deps стали иметь тип=D/G, в качестве официального имени подразделения выберем ближающую
'  по иерархии запись с TypeCode='D'
'20100920 SNS Данные по сотрудникам, подписавшим приказ будем брать из таблицы SNS.StfOrd_Signers
%>

<!--#include virtual="/inc/decodestr.asp"-->
<!--#include virtual="/inc/IsStfWorkNow.asp"-->
<!--#include virtual="/inc/OrgFullNameByID.asp"-->
<!--#include file="getstfmngid.asp"-->
<!--#include file="../dbopen.asp"-->
<%
'Set tConn = Server.CreateObject("ADODB.Connection")
'tConn.Open "orders"

thisTitle="Печать приказа"
thisScript=Request.ServerVariables("SCRIPT_NAME")
OrgID="0000000001"
OrgName="ОАО &quot;Приморское агентство авиационных компаний&quot;"

ID=trim(request.QueryString("ID"))
if ID<>"" then

	'Read data from Table -----------------------------------
	SQL="select * from STFORDERS where ID='"&ID&"'"
	Set RS=Conn.Execute(SQL)
	if not rs.eof then
		StaffID=trim(rs("StaffID"))
		OrdTypeID=trim(trim(rs("OrdTypeID")))
		ORDTYPE=ucase(trim(rs("ORDTYPE")))
		OrdNum=trim(rs("OrdNum"))
		OrdDate=trim(rs("OrdDate"))
		BEGDATE=trim(rs("BEGDATE"))
		ENDDATE=trim(rs("ENDDATE"))
		DEPID=trim(rs("DEPID"))
		POSTID=trim(rs("POSTID"))
		SALARY=trim(rs("SALARY"))
		Reason=trim(rs("Reason"))

		WHOCRT=trim(rs("WHOCRT"))
		WHNCRT=trim(rs("WHNCRT"))
		WHOCHNG=trim(rs("WHOCHNG"))
		WHNCHNG=trim(rs("WHNCHNG"))
		EDOCID=trim(rs("EDOCID"))
		rs.close

		if isNull(OrdDate) or OrdDate=" " then OrdDate="-"
		if isNull(EndDate) or EndDate=" " then EndDate="&nbsp;"

		select case OrdType
		case "JOBBEG"
			'ordTypeName="о приеме<br> работника <br>на работу"
			ordTypeName="о приеме работника на работу"

			Signer3ID="_0SU0WBM44"
			Signer4ID="_0P614NZZ5"

			SQL="select JobType,Salary,Bonus,TestTerm from OrdJobBeg where id='"&ID&"'"
			'response.write "<br>"&SQL
			Set RS=Conn.Execute(SQL)
			if not rs.eof then
				JobType=trim(rs("JobType"))
				Salary=trim(rs("Salary"))
				Bonus=trim(rs("Bonus"))
				TestTerm=trim(rs("TestTerm"))
			end if


		case "BUSNTRIP"
			ordTypeName="(распоряжение)<br>о направлении работника в командировку"

			'Если начальник в данный момент сам находится в отпуске/командировке - не выводить его вообще
			if not IsStfWorkNow(Signer3ID) then Signer3ID=""
			'начальник подразделения может совпасть с руководителем организации или гл. бухгалтером
			if Signer3ID=Signer1ID or Signer3ID=Signer2ID then Signer3ID=""	' - тогда не выводить

			Signer4ID=""

			SQL="select * from OrdBusnTrip where id='"&ID&"'"
			'response.write "<br>"&SQL
			Set RS=Conn.Execute(SQL)
			if not rs.eof then
				Destination=trim(rs("Destination"))
				FinSrc=trim(rs("FinSrc"))
				Goal=trim(rs("Goal"))
			end if
			TripDays=cdate(EndDate)-cdate(BegDate)+1

		case "BONUSTRIP"
			'SQL="select Name from sns.StfOrdTypes where ID='"&OrdTypeID&"'"
			'response.write "<br>"&SQL
			'Set RS=Conn.Execute(SQL)
			'if not rs.eof then
			'	OrdTypeName=trim(RS("Name"))
			'end if
			'rs.close

			ordTypeName="По личному составу"

		    SQL="select * from StfOrd_BonusTrip where id='"&ID&"'"
		   'response.write "<br>"&SQL
			Set RS=Conn.Execute(SQL,,adCmdText)
		    if not rs.eof then
        		SubRsnType=trim(rs("SubRsnType"))
        		Route=trim(rs("Route"))
		        UseBegDate=trim(rs("UseBegDate"))
		        UseEndDate=trim(rs("UseEndDate"))
		        DepDate=trim(rs("DepDate"))
		        ArvDate=trim(rs("ArvDate"))
				BonusSum=trim(rs("BonusSum"))
				ExtraPerson=trim(rs("ExtraPerson"))

		        if isNull(SubRsnType) then SubRsnType=""	'1-по закону №4520-1, 2-по положению Аг-ва
		        if isNull(BonusSum) then BonusSum="0"
				if isNull(ExtraPerson) then ExtraPerson=""

		 	end if

		end select

		if StaffID<>"" then
			SQL="select OrgID, PostID, LName,FName,MName, TabNum, AltDepName from orgStaff where id='"&StaffID&"'"
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

		'20170405 SNS. Выясним полное название организации по состоянию на дату приказа
		OrgName = OrgFullNameByID(OrgID, OrdDate)

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
			'SQL="select Name from Deps where ID='"+DepID+"'"
			'110401 SNS в связи с тем, что записи в Deps стали иметь тип=D/G, в качестве официального имени подразделения выберем ближающую
			' по иерархии запись с TypeCode='D'
			sql = "select * from (SELECT level, d.* FROM deps d"
			sql = sql & " where d.typecode = 'D'"
			sql = sql & " START WITH d.id = '" & DepID & "'"
			sql = sql & " CONNECT BY PRIOR parid = id)"
			sql = sql & " where rownum = 1"
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
<link rel=stylesheet type="text/css" href="../app.css">
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
</head>

<body topmargin="0" leftmargin="0" rightmargin="0" bottommargin="0" onLoad="Print();">
<!--<body onload="window.print();window.close();">-->
<!-- MeadCo ScriptX -->
<object id=factory style="display:none"
  classid="clsid:1663ed61-23eb-11d2-b92f-008048fdd814"
  codebase="http://192.168.1.231/_tools/ScriptX.cab#Version=6,1,432,1">
</object>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr><td style="width: 1cm;"> </td><td>

<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center" class="fnt12">
<tr class="fnt9">
	<td align="right">Унифицированная форма #Т-1
	<br>Утверждена Постановлением Госкомстата
	<br>России от 06.04.2001 №26</td>
</tr>
<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" cellspacing="0" cellpadding="1" border="0" class="fnt9">
	<tr><td rowspan="3" width="*">
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr class="fnt12">
				<td class="brd1blck_b"><b><%=OrgName%></b></td>
			</tr>
			<tr><td align="center" class="fnt8"><sup>наименование организации</sup></td>
			</tr>
			</table>
		</td>
		<td align="right" width="180"></td>
		<td align="center" width="128" class="brd1blck_ltr">Код</td>
	</tr>
	<tr>
		<td align="right">&nbsp;Форма по ОКУД</td>
		<td align="center" class="brd1blck_ltr">0301001</td>
	</tr>
	<tr>
		<td align="right">&nbsp;по ОКПО</td>
		  <td align="center" class="brd1blck_ltrb">42072462</td>
	</tr>
	</table>
</td></tr>
<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt9">
	<td width="279" rowspan="2"></td>
	      <th width="94" rowspan="2" valign="bottom" class="fnt16">ПРИКАЗ&nbsp;</th>
	<td rowspan="2" width="24"></td>
	<td width="118" class="brd1blck_lt">Номер</td>
	<td width="127" class="brd1blck_ltr">Дата</td>
	</tr>
	<tr align="center" class="fnt12b">
	<td class="brd1blck_ltb"><%=OrdNum%></td>
	<td class="brd1blck_ltrb"><%=OrdDate%></td>
	</tr>
	<tr align="center">
	<td class="fnt12b" colspan="5"><%=OrdTypeName%></td>
	</tr>
	</table>
</td></tr>
<tr><td height="6"></td></tr>

<%select case OrdType
case "JOBBEG"%>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt9">
	<td></td>
	<td></td>
	<td class="brd1blck_ltr">Дата</td>
	</tr>
	<tr align="center" class="fnt12">
	<td align="right"><b>Принять на работу</b>&nbsp;</td>
	<td width="100" class="brd1blck_lt">с</td>
	<td width="128" class="brd1blck_ltr"><b><%=BegDate%></b></td>
	</tr>
	<tr align="center" class="fnt12">
	<td></td>
	<td class="brd1blck_ltb">по</td>
	<td class="brd1blck_ltrb"><b><%=EndDate%></b></td>
	</tr>
	</table>

</td></tr>

<%case "BUSNTRIP"%>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
	<td align="left">Направить в командировку:&nbsp;</td>
	</tr>
	</table>

</td></tr>

<%case "BONUSTRIP"
	select case SubRsnType
	case "1"	'по закону №4520-1 от 19.02.1993
		OrdSubject="В целях компенсации расходов по оплате стоимости проезда к месту использования отпуска и обратно согласно Закона РФ №4520-1 от 19.02.1993г. предоставить проезд"
	case "2"
		OrdSubject="Предоставить льготный проезд"
	case else
		OrdSubject="Предоставить льготный проезд"
	end select%>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
	<td align="left"><b><%=OrdSubject%></b>:&nbsp;</td>
	</tr>
	</table>

</td></tr>
<%end select%>

<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center">
	<td rowspan="2" valign="bottom" class="brd1blck_b"><b><%=PrsnFIO%></b></td>
	<td width="128" class="brd1blck_ltr"><font class="fnt9">Табельный номер</font></td>
	</tr>
	<tr>
	<td align="center" class="brd1blck_ltrb"><font class="fnt10">&nbsp;<%=TabNum%>&nbsp;</font></td>
	</tr>
	<tr align="center">
		<td class="fnt8"><sup>Фамилия Имя Отчество</sup></td>
		<td></td>
	</tr>
	</table>
</td></tr>

<%select case OrdType
case "JOBBEG"%>
<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td width="24">в</td>
		<td valign="bottom" class="brd1blck_b"><b><%=DepName%></b></td>
	</tr>
	<tr align="center">
		<td></td>
		<td class="fnt8"><sup>Наименование структурного подразделения</sup></td>
	</tr>
	</table>
</td></tr>

<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td valign="bottom" class="brd1blck_b"><b><%=PostName%></b></td>
	</tr>
	<tr align="center">
		<td class="fnt8"><sup>должность (специальность, профессия), разряд, класс (категория) квалификации</sup></td>
	</tr>
	</table>
</td></tr>

<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td valign="bottom" class="brd1blck_b">&nbsp;<b><%=JobType%></b>&nbsp;</td>
	</tr>
	<tr align="center">
		<td class="fnt8"><sup>условия приема на работу, характер работы</sup></td>
	</tr>
	</table>
</td></tr>

<tr><td height="6"></td></tr>

<tr><td>
	<table border="0" cellspacing="0" cellpadding="2" align="center" class="fnt12">
	<tr>
	<td height="24" align="right">с окладом (тарифной ставкой)&nbsp;</td>
	<td align="center" class="brd1blck_b">&nbsp;<b><%=formatNumber(Salary,2,,,0)%> рублей</b>&nbsp;</td>
	<td></td>
	</tr>
	<tr>
	<td height="24" align="right">надбавкой&nbsp;</td>
	<td align="center" class="brd1blck_b">&nbsp;<b><%=Bonus%></b>&nbsp;</td>
	<td></td>
	</tr>
	<tr>
	<td height="24" align="right">с испытанием на срок&nbsp;</td>
	<td align="center" class="brd1blck_b">&nbsp;<b><%=TestTerm%></b>&nbsp;</td>
	<td class="fnt10">месяца (ев)</td>
	</tr>
	</table>
</td></tr>

<%case "BUSNTRIP"%>
<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td valign="bottom" class="brd1blck_b"><b><%=PostName%></b></td>
	</tr>
	<tr align="center">
		<td class="fnt8"><sup>должность (специальность, профессия), разряд, класс (категория) квалификации</sup></td>
	</tr>
	</table>
</td></tr>

<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td valign="bottom" class="brd1blck_b"><b><%=DepName%></b></td>
	</tr>
	<tr align="center">
		<td class="fnt8"><sup>Наименование структурного подразделения</sup></td>
	</tr>
	</table>
</td></tr>

<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td valign="bottom" class="brd1blck_b">&nbsp;<b><%=Destination%></b>&nbsp;</td>
	</tr>
	<tr align="center">
		<td class="fnt8"><sup>место назначения (страна, город, организация)</sup></td>
	</tr>
	</table>
</td></tr>
<tr><td height="15" colspan="2"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2" align="left" class="fnt12">
        <tr> 
          <td width="80" height="35" align="right" >сроком на&nbsp;</td>
          <td align="center" class="brd1blck_ltrb_bold">&nbsp;<b><%=TripDays%></b>&nbsp;</td>
          <td width="75%">&nbsp;календарных дней</td>
        </tr>
        <tr> 
          <td height="30" colspan="3"> &nbsp;с <b><%=formatDateTime(BegDate,1)%></b> по <b><%=formatDateTime(EndDate,1)%></b>&nbsp;</td>
        </tr>
      </table>
</td></tr>
<tr><td height="15" colspan="2"></td></tr>
<tr><td height="24">
	<table width="100%" border="0" cellspacing="0" cellpadding="2" class="fnt12">
	    <tr> 
          <td width="56" height="35" class="fnt12">с целью&nbsp;</td>
		  <td align="center" valign="bottom" class="brd1blck_b">&nbsp;<b><%=Goal%></b>&nbsp;</td>
	</tr>
	</table>
</td></tr>
<tr><td height="6" colspan="2"></td></tr>
<tr><td >
	<table width="100%" border="0" cellspacing="0" cellpadding="2" class="fnt12">
	    <tr> 
          <td width="186" height="35" class="fnt12">Командировка за счет средств&nbsp;</td>
		  <td align="center" valign="bottom" class="brd1blck_b">&nbsp;<b><%=FinSrc%></b>&nbsp;</td>
	</tr>
	</table>
</td></tr>

<%case "BONUSTRIP"%>
<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td valign="bottom" class="brd1blck_b"><b><%=PostName%></b></td>
	</tr>
	<tr align="center">
		<td class="fnt8"><sup>должность (специальность, профессия), разряд, класс (категория) квалификации</sup></td>
	</tr>
	</table>
</td></tr>

<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td valign="bottom" class="brd1blck_b"><b><%=DepName%></b></td>
	</tr>
	<tr align="center">
		<td class="fnt8"><sup>Наименование структурного подразделения</sup></td>
	</tr>
	</table>
</td></tr>

<%if ExtraPerson<>"" then%>
<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td valign="bottom" class="brd1blck_b">&nbsp;дополнительно следует: <b><%=ExtraPerson%></b>&nbsp;</td>
	</tr>
	</table>
</td></tr>
<tr><td height="15" colspan="2"></td></tr>
<%else%>
<tr><td height="6"></td></tr>
<%end if%>

<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td valign="bottom" class="brd1blck_b">&nbsp;по маршруту: <b><%=Route%></b>&nbsp;</td>
	</tr>
	</table>
</td></tr>

<tr><td height="15" colspan="2"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td valign="bottom" class="brd1blck_b">&nbsp;даты выезда-приезда: <b><%=DepDate%></b> - <b><%=ArvDate%></b>&nbsp;</td>
	</tr>
	</table>
</td></tr>

<tr><td height="15" colspan="2"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td valign="bottom" class="brd1blck_b">&nbsp;Льготный проезд считать использованным за период: <b><%=UseBegDate%></b> - <b><%=UseEndDate%></b>&nbsp;</td>
	</tr>
	<tr align="center">
		<td class="fnt8"></td>
	</tr>
	</table>
</td></tr>

<tr><td height="15" colspan="2"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr align="center" class="fnt12">
		<td valign="bottom" class="brd1blck_b">&nbsp;Бухгалтерии произвести оплату проезда в размере <b><%=formatNumber(BonusSum,2)%></b> рублей&nbsp;</td>
	</tr>
	<tr align="center">
		<td class="fnt8"></td>
	</tr>
	</table>
</td></tr>

<%end select%>

<tr><td height="6" colspan="2"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2" class="fnt12">
	<tr align="center">
		  <td width="72" height="35" align="left" class="fnt12">Основание:</td>
		<td valign="bottom" class="brd1blck_b"><%=Reason%>&nbsp;</td>
	</tr>
	</table>
</td></tr>

<%select case OrdType
case "JOBBEG"%>
<tr><td height="6"></td></tr>
<tr><td>Трудовой договор (контракт) от "&nbsp;&nbsp;&nbsp;"
</td></tr>
<%end select%>

<tr><td height="6"></td></tr>
<tr>
    <td height="30"></td>
  </tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
        <tr valign="middle" class="fnt12"> 
          <td width="220" class="fnt11"><b>Руководитель организации</b></td>
          <td valign="bottom" class="brd1blck_b"><b><%=Signer1Post%></b></td>
          <td width="100" align="center" valign="bottom" class="brd1blck_b"><b>&nbsp;</b></td>
          <td align="right" valign="bottom" class="brd1blck_b"><b><%=Signer1FIO%></b></td>
        </tr>
        <tr> 
          <td align="center"></td>
          <td align="center" class="fnt8"><sup>должность</sup></td>
          <td align="center" class="fnt8"><sup>подпись</sup></td>
          <td align="center" class="fnt8"><sup>расшифровка подписи</sup></td>
        </tr>
        <%if OrdType="BUSNTRIP" then%>
        <tr valign="middle" class="fnt12"> 
          <td class="fnt11"><b>Согласовано:</b></td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
			<tr><td height="6" colspan="4"></td></tr>
        <tr valign="middle" class="fnt12"> 
          <td width="220" class="fnt11">&nbsp;</td>
          <td valign="bottom" class="brd1blck_b"><b><%=Signer2Post%></b></td>
          <td width="100" align="center" valign="bottom" class="brd1blck_b"><b>&nbsp;</b></td>
          <td align="right" valign="bottom" class="brd1blck_b"><b><%=Signer2FIO%></b></td>
        </tr>
        <tr> 
          <td align="center"></td>
          <td align="center" class="fnt8"><sup>должность</sup></td>
          <td align="center" class="fnt8"><sup>подпись</sup></td>
          <td align="center" class="fnt8"><sup>расшифровка подписи</sup></td>
        </tr>
			<tr>
          <td height="20" colspan="4"></td>
        </tr>
        <tr valign="middle" class="fnt12"> 
          <td width="220">&nbsp;</td>
          <td valign="bottom" class="brd1blck_b"><b><%=Signer3Post%></b>&nbsp;</td>
          <td width="100" align="center" valign="bottom" class="brd1blck_b">&nbsp;</td>
          <td align="right" valign="bottom" class="brd1blck_b"><b><%=Signer3FIO%></b>&nbsp;</td>
        </tr>
        <tr> 
          <td align="center"></td>
          <td align="center" class="fnt8"><sup>должность</sup></td>
          <td align="center" class="fnt8"><sup>подпись</sup></td>
          <td align="center" class="fnt8"><sup>расшифровка подписи</sup></td>
        </tr>
			<%if Signer4ID<>"" then%>
			<tr>
          <td height="20" colspan="4"></td>
        </tr>
			<tr valign="middle" class="fnt12"> 
			  <td width="220">&nbsp;</td>
			  <td valign="bottom" class="brd1blck_b"><b><%=Signer4ID%><%=Signer4Post%></b></td>
			  
          <td width="100" align="center" valign="bottom" class="brd1blck_b"></td>
			  <td align="right" valign="bottom" class="brd1blck_b"><b><%=Signer4FIO%></b></td>
			</tr>
			<tr> 
			  <td align="center"></td>
			  <td align="center" class="fnt8"><sup>должность</sup></td>
			  <td align="center" class="fnt8"><sup>подпись</sup></td>
			  <td align="center" class="fnt8"><sup>расшифровка подписи</sup></td>
			</tr>
			<%end if%>
			<tr> 
			  <td colspan="4" height="20"></td>
			</tr>
        <%end if 'OrdType<>"BUSNTRIP"%>
      </table>
	
</td></tr>


<tr><td height="6"></td></tr>
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	    <tr valign="middle" class="fnt12"> 
          <td width="220" class="fnt11"><b>С приказом (распоряжением) работник 
            ознакомлен</b></td>
		  <td width="100" align="center" valign="bottom" class="brd1blck_b"><b>&nbsp;</b></td>
		  <td align="right" valign="bottom">&nbsp;"&nbsp;&nbsp;&nbsp;&nbsp;" _______________ 
            20___ года</td>
	</tr>
	    <tr> 
          <td></td>
		  <td align="center" class="fnt8"><sup>подпись работника</sup></td>
		  <td></td>
		  <td></td>
	</tr>
	</table>
</td></tr>

<%if OrdType<>"BUSNTRIP" then%>
	<tr><td height="6"></td></tr>
	<tr><td>
		<table border="0" cellspacing="0" cellpadding="2" class="fnt12b">
	<tr>
	  <td width="220" height="24" class="fnt11"><b>Согласовано:</b></td>
		<td></td>
		<td></td>
	</tr>
	<tr valign="bottom">
		<td height="32">&nbsp;<%=Signer2Post%>&nbsp;</td>
		<td width="128" class="brd1blck_b">&nbsp;&nbsp;</td>
		<td>&nbsp;<%=Signer2FIO%>&nbsp;</td>
	</tr>
	<tr valign="bottom">
		<td height="32">&nbsp;<%=Signer3Post%>&nbsp;</td>
		<td width="128" class="brd1blck_b">&nbsp;&nbsp;</td>
		<td>&nbsp;<%=Signer3FIO%>&nbsp;</td>
	</tr>
	<%if Signer4ID<>"" then%>
	<tr valign="bottom">
		<td height="32">&nbsp;<%=Signer4Post%>&nbsp;</td>
		<td width="128" class="brd1blck_b">&nbsp;&nbsp;</td>
		<td>&nbsp;<%=Signer4FIO%>&nbsp;</td>
	</tr>
	<%end if%>
</table>
</td></tr>
<%end if 'OrdType<>"BUSNTRIP"%>

</table>
</td></tr></table>
</body></html>
<!--#include file="../dbclose.asp"-->
