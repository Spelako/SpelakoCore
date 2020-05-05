# Spelako Project
此仓库为 Spelako 的后端源代码. Spelako 是由 Peaksol 编写的 QQ 机器人, 提供查询玩家 Hypixel 统计信息等功能.

# Spelako 是如何工作的
此仓库中的所有代码都被放在一个 PHP 服务器上. 然后通过对 QQ 机器人框架(如酷Q)的开发, 使其在每次接收到消息后, 都向后端服务器发送 GET 请求. 服务器会直接返回命令执行结果的**纯文本**(如果是无效的命令则返回空). 然后通过机器人框架将服务器返回的文本回复到 QQ.

# Spelako 名字的由来
Spelako 最初的名字为 PeaksolBot, 后改名为 Spelako. "Spelako" 实际上是对 "Peaksol" 中每个字母进行了重新排序.