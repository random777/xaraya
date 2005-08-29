<?php
/**
 *  @package   phing.lib
 */
class gaugebox {
    function gaugebox($msg) {
        $this->msg = "Installing: $msg";
        $this->size_done = 0;
        $this->total_size = 0;
        if (defined('DIALOG')) {
            get_max_size($dlgx, $dlgy);
            $this->dlgx = $dlgx;
            if	(($this->fd=popen(DIALOG." --gauge '' $dlgy $dlgx 0", 'w')) == false)
                return false;
        } else {
            print ("$this->msg\r");
        }
    }

    function close() {
        if (defined('DIALOG'))
            pclose($this->fd);
        else
            print ("\n");
    }

    function update($filename, $dest) {
        $this->percent = (int)(($this->size_done / $this->total_size) * 100);
        if (defined('DIALOG')) {
            fwrite ($this->fd, "XXX\n");
            fflush($this->fd);
            fwrite ($this->fd, "$this->percent\n");
            fflush($this->fd);
            fwrite ($this->fd, $this->msg."\n\n");
            fflush($this->fd);
            fwrite ($this->fd, "Copying: ".basename($filename)."\nTo: $dest\n");
            fflush($this->fd);
            fwrite ($this->fd, "XXX\n");
            fflush($this->fd);
        } else {
            printf ("%- 40s ... [%%%d]\r", $this->msg, $this->percent);
        }
    }

    var $fd, $dlgx, $msg;
    var $total_size, $size_done, $percent;
    var $update_bar;
}

?>
