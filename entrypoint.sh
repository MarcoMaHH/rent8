#!/bin/sh

# 启动 cron 服务，并将其放到后台运行
crond start

# 添加 cron 任务，每分钟执行一次 curl 命令
# echo '* * * * * curl http://localhost/admin/subscribe/execute' >/etc/crontabs/root
echo '57 8 * * * curl http://localhost/admin/subscribe/link' > /etc/crontabs/root
echo '0 9 * * * curl http://localhost/admin/subscribe/execute' >> /etc/crontabs/root

echo '启动成功'

# 启动 Apache 服务
httpd -D FOREGROUND

# 保持容器运行，可以通过在脚本末尾添加一个无限循环来实现
# 但由于 httpd -D FOREGROUND 已经会保持前台运行，所以这一行通常不是必需的
# while true; do sleep 1000; done
