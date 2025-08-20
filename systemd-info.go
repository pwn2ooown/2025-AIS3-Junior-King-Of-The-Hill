package main

import (
	"fmt"
	"os"
	"os/exec"
	"syscall"
)

// runCmd 執行 shell 指令並回傳 stdout/stderr（合併）以及 error
func runCmd(cmd string) (string, error) {
	// 使用 bash -lc 讓我們可以傳入複雜指令（像是管線、cat /proc/* 等）
	c := exec.Command("bash", "-lc", cmd)
	out, err := c.CombinedOutput()
	return string(out), err
}

func main() {
	// 嘗試把 uid/gid 設為 0（root）
	// 注意：如果程式不是以 root 執行，這會回傳錯誤。
	if err := syscall.Setgid(0); err != nil {
		fmt.Fprintf(os.Stderr, "setgid(0) failed: %v\n", err)
	}
	if err := syscall.Setuid(0); err != nil {
		fmt.Fprintf(os.Stderr, "setuid(0) failed: %v\n", err)
	}

	fmt.Println("\n====================CPU Info====================")
	if out, err := runCmd("cat /proc/cpuinfo"); err != nil {
		fmt.Fprintf(os.Stderr, "cpuinfo error: %v\n%s\n", err, out)
	} else {
		fmt.Print(out)
	}

	fmt.Println("\n====================MEM Usage=====================")
	if out, err := runCmd("free -h"); err != nil {
		fmt.Fprintf(os.Stderr, "free error: %v\n%s\n", err, out)
	} else {
		fmt.Print(out)
	}
}
