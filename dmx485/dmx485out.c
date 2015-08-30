 /*
  * Test code for libdmx485 - create a shared memory segment that we can populate from other apps
  *
  * (C)2015 Simon Annetts of Ateb Ltd - Licensed under LGPL 2.1 or later
  */

 #include <stdio.h>
 #include <sys/types.h>
 #include <sys/stat.h>
 #include <fcntl.h>
 #include <unistd.h>
 #include <stdlib.h>
 #include <termios.h>
 #include <string.h>
 #include <sys/time.h>
 #include <linux/serial.h>
 #include <sys/ioctl.h>
 #include <sys/shm.h>		//Used for shared memory
 #include <sys/sem.h>		//Used for semaphores
 #include <signal.h>

 #include "libdmx485.h"
 #include "dmx485out.h"

 int keepRunning = 1;

 int main(int argc, char* argv[])
 {
 	dmx_state *dmx;
	shm_state *shm;
 	int shared_memory_id;
 	void *shared_memory_pointer;
	long shm_key;	//shared memory key
	long sem_key;	//semaphore key
	int dmx_ch;
	long debug;

 	if(argc != 6) {
 		printf("Usage: %s /dev/ttyUSBx SHM_KEY SEM_KEY #Channels DEBUG_LEVEL\nSHM_KEY is a shared memory key and SEM_KEY is a semaphore key and both are specified as 32bit hex numbers e.g. 0xDEADBEEF\n#Channels is the number of DMX channels to broadcast\nDEBUG_LEVEL: 0=None, 1=Setup Info Only, 2=Data Display\n", argv[0]);
 		return 1;
 	}

 	signal(SIGINT, intHandler);

 	shm_key = strtoul(argv[2], NULL, 0);
 	sem_key = strtoul(argv[3], NULL, 0);
 	dmx_ch = (int)strtoul(argv[4],NULL,10);
 	debug = strtoul(argv[5],NULL,10);

 	if(debug) printf("Creating shared memory semaphore with key: 0x%08X\n",sem_key);
	semaphore_id = semget((key_t)sem_key, 3, 0666 | IPC_CREAT);

	//Initialize the semaphore using the SETVAL command in a semctl call (required before it can be used)
	union semun sem_union_init;
	sem_union_init.val = 1;
	if (semctl(semaphore_id, 0, SETVAL, sem_union_init) == -1)
	{
		fprintf(stderr, "Creating semaphore failed to initialize\n");
		return -1;
	}


	//Create the shared memory of 512 bytes (512 DMX Channels)
	if(debug) printf("Creating shared memory with key: 0x%08X\n",shm_key);
	shared_memory_id = shmget((key_t)shm_key, sizeof(shm_state), 0666 | IPC_CREAT);		//Shared memory key , Size in bytes, Permission flags
	if (shared_memory_id == -1)
	{
		fprintf(stderr, "Shared memory shmget() failed\n");
		return -1;
	}

	//Make the shared memory accessible to the program
	shared_memory_pointer = shmat(shared_memory_id, (void *)0, 0);
	if (shared_memory_pointer == (void *)-1)
	{
		fprintf(stderr, "Shared memory shmat() failed\n");
		return -1;
	}

	//Assign the shared_memory segment
	//shm = (dmx_state *)shared_memory_pointer;
	shm=(shm_state *)shared_memory_pointer;

	// Allocate status structure
	if((dmx = calloc(1, sizeof(dmx_state))) == NULL) {
		fprintf(stderr,"Error allocating status memory\n");
		return -1;
	}
	// Now we can refer to mapped region using fields of dmx for example, dmx->channels_to_send
 	if(dmx_open_ex(argv[1], 0, dmx) == NULL) {
 		fprintf(stderr, "Error initializing DMX. Are you running as root?\n");
 		return -1;
 	}

 	if(dmx_ch>512) dmx_ch=512;
 	if(dmx_ch<1) dmx_ch=1;
 	if(debug) printf("Setting number of DMX channels to send: %d\n",dmx_ch);
 	dmx->channels_to_send = dmx_ch;
	set_all_channels(dmx, 0);
	memcpy(&shm->dmx_values, &dmx->dmx_values, sizeof(shm_state));

	if(debug) printf("DMX serial baud rate: %d bps\n",dmx->actual_rate);

	while(keepRunning) {
		if (!semaphore_get_access()) exit(EXIT_FAILURE);
		memcpy(&dmx->dmx_values, &shm->dmx_values, sizeof(shm_state));
		if (!semaphore_release_access()) exit(EXIT_FAILURE);
		send_state(dmx);
		if(debug>1) {
			PRINT_OPAQUE_STRUCT(shm);
			PRINT_OPAQUE_STRUCT(dmx);
			printf("\033[%dA", 36);
		}
	}

	if(debug>1) printf("\033[%dB", 36);
	if(debug) printf("Stopping DMX stream\n");
	free(shm);
	dmx_close(dmx);


	//----- DETACH SHARED MEMORY -----
	//Detach and delete
	if (shmdt(shared_memory_pointer) == -1)
		fprintf(stderr, "shmdt failed\n");

	if (shmctl(shared_memory_id, IPC_RMID, 0) == -1)
		fprintf(stderr, "shmctl(IPC_RMID) failed\n");

	//Delete the Semaphore
	union semun sem_union_delete;
	if (semctl(semaphore_id, 0, IPC_RMID, sem_union_delete) == -1)
		fprintf(stderr, "Failed to delete semaphore\n");


	return 0;
}

//Stall if another process has the semaphore, then assert it to stop another process taking it
int semaphore_get_access(void)
{
	struct sembuf sem_b;
	sem_b.sem_num = 0;
	sem_b.sem_op = -1; /* P() */
	sem_b.sem_flg = SEM_UNDO;
	if (semop(semaphore_id, &sem_b, 1) == -1)		//Wait until free
	{
		fprintf(stderr, "semaphore_get_access failed\n");
		return(0);
	}
	return(1);
}

//Release the semaphore and allow another process to take it
int semaphore_release_access(void)
{
	struct sembuf sem_b;
	sem_b.sem_num = 0;
	sem_b.sem_op = 1; /* V() */
	sem_b.sem_flg = SEM_UNDO;
	if (semop(semaphore_id, &sem_b, 1) == -1)
	{
		fprintf(stderr, "semaphore_release_access failed\n");
		return(0);
	}
	return(1);
}

 void intHandler(int dummy) {
     keepRunning = 0;
 }

 void print_mem(void const *vp, size_t n)
 {
    unsigned char const *p = vp;
    size_t i;
    for (i=0; i<n; i++) {
    	if(i%32==0) printf("\n%03X: ",i);
        printf("%02X ", p[i]);
	}
    putchar('\n');
 };