#!/bin/bash
pwd ; cd .. ; rm -rf hws ; git clone "https://hws-git.i.hwstudio.hu/hws-Alkalmazas/hws-vue.git" "hws" -b "v1.x" ; rm -rfv hws/.git ; chmod u+x hws/update.sh