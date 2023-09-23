<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns:v="urn:schemas-microsoft-com:vml">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;" />
        <meta name="viewport" content="width=600,initial-scale = 2.3,user-scalable=no">

        <link href="https://fonts.googleapis.com/css?family=Work+Sans:300,400,500,600,700" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Quicksand:300,400,700" rel="stylesheet">
    <title> OTP Verification </title>
</head>
<body style="padding: 10px;  ">
    <center><img src="{{ asset('logo.png') }}" style="width: 300px; height: auto;" alt=""></center>
<p>&nbsp; &nbsp;&nbsp;</p>

<center><h2><span style="font-family: Montserrat"><strong>{{$details['subject']}}&nbsp;</strong></span></h2></center>
<p style="line-height: 24px;margin-bottom:15px;">
    Your call-a-doc Admin OTP is: <br><br>
</p>

<p>

    <span></span> <span><em>{{$details['otp']}}</em></span>
</p>


<p>&nbsp;</p>

</body>

</html>
