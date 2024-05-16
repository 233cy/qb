
[img1]https://cdnjson.com/images/2024/05/16/121f42cfd461a3c0a.png[/img]
[img1]https://cdnjson.com/images/2024/05/16/123cba874b3ee3de2b7.png[/img]


如图，会列出四种可能的数据：1小于多少kb的文件夹（用于判定是否为空文件夹，因为有的只有10来kb或者几十kb，是完全不需要的文件夹，且也会说明有没有对应任务）。2、列出文件夹监测不到有对应的qb任务的。3、文件夹大小和任务大小不一致的。4、qb任务没有对应文件夹的（即为qb任务存在但是整个文件夹都消失了属于无效任务）。

首先说下执行逻辑：1、读取下载目录。2、遍历下载目录（不管是文件还是文件夹都只获取一级名称）。3、根据qb接口获取所有任务信息。4、通过遍历得到的一级目录或文件和qb任务内容对比（注qb接口种反馈的就是一级文件或目录）。以上是操作的大致逻辑，不会出现误判问题，并且担心的话可以自行手动对比一下。因为脚本默认只是进行展示和过滤数据展示。（部分过滤数据是有删除快捷操作）

接下来说下目前我知道的文件夹大小不一致问题：
1、因系统缘故导致任务文件大小和源文件大小不一致问题（多数是文件过多可能有其他隐藏文件不纠结这个，一般这个时候是源文件大于任务大小的，担心的话自行强制校验下即可）
2、如果你有a、b、c三个任务，分别是tv1的1、2、3集，但是他们都指向的是tv1目录，这个时候需要你人为去判定（一般如果源文件小于任务大小的时候都是需要你强制校验一下的）
3、如果tv1目录中有1、2集，但是你qb任务第1集的任务，这个时候就会发现源文件大于任务大小，但是因为没有做子文件或目录逐级对比，这里只能根据大小来判定，所以需要你人为去整理。或者这种你完全可以无视，因为当你第1集的任务消失的时候，你自然就知道tv1目录是空的（脚本会列出文件夹没有对应任务）




【：：：：：：：使用说明：：：：：：：】
这是一个php文件代码，只需要放在qb所属的机器上运行一个php服务即可。
本人是用群晖nas，群晖nas自行百度web Station如何增加php站点(https://post.smzdm.com/p/am3dd6wz/)
你只需要把这个php文件仍在php环境中，然后通过http访问即可。

：：：docker：：：
你只需要pull一个php环境就可以，直接运行这个脚本（具体可百度）

：：：宝塔：：：
宝塔很简单，创建一个站点，增加一个php文件然后写入代码就可以。


：：：：：：：：：：：：注意不懂的小伙伴，上面是代码，你需要在站点环境中创建一个*.php文件就可以：：：：




：：：：注意事项：：：：
不管你在什么环境中，要满足，php环境是可以读取到你本地的qb文件夹目录的。然后也是可以访问到你qb地址的。且建议将你下载目录权限改为755，或者777也都可以（即为代码中顶部需要配置的几处qbDownDir）。



如果有什么不懂的可以评论有空回复（不准备继续完善成小白用法，写这个脚本完全取决于强迫症，至于会不会出python版本后面再说吧）


【原创】【禁止转载】【个人随意使用也可二次开发（如果你不觉得代码太乱）】
