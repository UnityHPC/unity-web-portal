#!/usr/bin/env python3

import socket
import xml.etree.ElementTree as ET
import shutil
import os
import pwd
import grp
from _thread import *
import requests
from requests.auth import HTTPBasicAuth
import urllib3
import time
import select


urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

dirname = os.path.dirname(__file__)

HOST = '127.0.0.1'  # Standard loopback interface address (localhost)
PORT = 2010        # Port to listen on (non-privileged ports are > 1023)

NAS1_URL = "https://nas1.unity.rc.umass.edu/api/v2.0"

NAS1_API_KEY = ""
with open('apikey', 'r') as file:
    NAS1_API_KEY = file.read().replace('\n', '')
print(NAS1_API_KEY)

NAS1_HOME_DATASET = "nas1-pool/home"
NAS1_HEADERS = { 'Authorization': 'Bearer ' + NAS1_API_KEY }

TIME_WAIT = 0.01
TIMEOUT = 5  # timeout in seconds

def threaded_client(conn):
    NO_MESSAGE = 0

    while True:
        if (NO_MESSAGE * TIME_WAIT > TIMEOUT):
            # timeout
            break

        try:
            data = conn.recv(1024).decode('utf-8')

            try:
                tree = ET.ElementTree(ET.fromstring(data))
                root = tree.getroot()
                NO_MESSAGE = 0
            except:
                NO_MESSAGE += 1
                time.sleep(TIME_WAIT)
                continue
            
            if root.tag != "request":
                time.sleep(TIME_WAIT)
                continue

            req_type = root.attrib["type"]
            if req_type == "create_home":
                user = ""
                group = ""
                quota = ""

                for child in root:
                    if child.tag == "uid":
                        user = child.text
                    elif child.tag == "gid":
                        group = child.text
                    elif child.tag == "quota":
                        quota = child.text

                full_url = NAS1_URL + "/pool/dataset"
                dataset_path = NAS1_HOME_DATASET + "/" + user
                data = {
                    "name": dataset_path,
                    "quota": quota
                }

                # post request
                result = requests.post(full_url, json = data, headers = NAS1_HEADERS, verify = False)
                code = result.status_code
                print(result)
                if code != 200 and code != 422:
                    error = "<response code=" + str(code) + ">Error creating dataset</response>"
                    conn.send(error.encode())
                    time.sleep(TIME_WAIT)
                    continue

                # set perms
                data = {
                    "user": user,
                    "group": group,
                    "mode": "700",
                    "acl": [],
                    "options": {
                        "stripacl": True,
                        "recursive": True,
                        "traverse": True
                    }
                }

                full_url = NAS1_URL + "/pool/dataset/id/" + dataset_path.replace('/', '%2F') + "/permission"
                result = requests.post(full_url, json = data, headers = NAS1_HEADERS, verify = False)
                code = result.status_code
                print(result)
                if code != 200 and code != 422:
                    error = "<response code=" + str(code) + ">Error setting dataset permissions</response>"
                    conn.send(error.encode())
                    time.sleep(TIME_WAIT)
                    continue

                full_url = NAS1_URL + "/sharing/nfs"
                mnt_path = "/mnt/" + dataset_path
                data = {
                    "paths": [mnt_path],
                    "comment": "",
                    "networks": [
                        "10.100.0.0/16",
                        "10.10.0.0/16"
                    ],
                    "hosts": [],
                    "alldirs": True,
                    "ro": False,
                    "quiet": False,
                    "maproot_user": "",
                    "maproot_group": "",
                    "mapall_user": "",
                    "mapall_group": "",
                    "security": [
                        "SYS"
                    ],
                    "enabled": True
                }

                result = requests.post(full_url, json = data, headers = NAS1_HEADERS, verify = False)
                code = result.status_code
                print(result)
                if code != 200 and code != 422:
                    error = "<response code=" + str(code) + ">Error creating NFS export</response>"
                    conn.send(error.encode())
                    time.sleep(TIME_WAIT)
                    continue

                conn.send("<response>Success</response>".encode())

        except Exception as e:
            print(e)
            xml_error = "<response code=1>" + str(e) + "</response>"
            conn.send(xml_error.encode())

        time.sleep(TIME_WAIT)

    conn.close()

s = socket.socket()
s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
try:
    s.bind((HOST, PORT))
except socket.error as e:
    print(str(e))

s.listen(5)

while True:
    (conn, addr) = s.accept()
    start_new_thread(threaded_client, (conn, ))

s.close()