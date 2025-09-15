<!DOCTYPE html>
<html>

<body>

    <h2>Payment Forms</h2>

    <form action="https://www.sbiepay.sbi/secure/AggregatorHostedListener" method="POST">
        <input type="hidden" name="EncryptTrans" value="<?php echo $EncryptTrans; ?>">

        <input type="hidden" name="merchIdVal" value="{{ $merchIdVal }}" />
        <input type="submit" value="Submit">
    </form>



</body>

</html>
